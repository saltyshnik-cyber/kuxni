"""Сбор базы живых русскоязычных Telegram-каналов для партнёрства и розыгрышей."""
import asyncio
import csv
import os
import re
from datetime import datetime, timedelta, timezone
from pathlib import Path

from dotenv import load_dotenv
from telethon import TelegramClient, errors, functions, types

BASE_DIR = Path(__file__).resolve().parent
ENV_FILE = BASE_DIR / ".env"
SESSION_FILE = str(BASE_DIR / "session")
OUTPUT_FILE = BASE_DIR / "channels.csv"

KEYWORDS = [
    "нейросети",
    "искусственный интеллект",
    "claude",
    "вайбкодинг",
    "cursor",
    "программирование",
    "фриланс",
    "маркетинг",
    "стартапы",
]

MIN_SUBSCRIBERS = 500
MAX_SUBSCRIBERS = 25000
MAX_POST_AGE_DAYS = 10
MIN_CYRILLIC_SHARE = 0.3   # доля кириллицы среди букв в названии + описании
SEARCH_LIMIT = 100
REQUEST_DELAY = 2          # секунд между запросами к API
MAX_RETRIES = 3
REQUIRE_CONTACT = False    # True — сохранять только каналы, где найден контакт

CSV_COLUMNS = [
    "Название канала",
    "Ссылка на канал",
    "Подписчики",
    "Контакт админа",
    "Описание канала",
]

MENTION_RE = re.compile(r"(?<![\w/@.])@([A-Za-z][A-Za-z0-9_]{3,31})\b")
TME_LINK_RE = re.compile(
    r"(?:https?://)?(?:www\.)?(?:t|telegram)\.me/((?:\+|joinchat/)?[A-Za-z0-9_\-]{4,})",
    re.IGNORECASE,
)


def load_credentials() -> tuple[int, str]:
    load_dotenv(ENV_FILE)
    api_id = os.getenv("API_ID", "").strip()
    api_hash = os.getenv("API_HASH", "").strip()

    if not (api_id.isdigit() and api_hash):
        print("API_ID / API_HASH не найдены в .env. Получите их на https://my.telegram.org")
        api_id = input("api_id: ").strip()
        api_hash = input("api_hash: ").strip()
        if not (api_id.isdigit() and api_hash):
            raise SystemExit("Некорректные api_id / api_hash.")
        with open(ENV_FILE, "a", encoding="utf-8") as file:
            file.write(f"API_ID={api_id}\nAPI_HASH={api_hash}\n")
        print(f"Данные сохранены в {ENV_FILE.name}")

    return int(api_id), api_hash


async def safe_call(func, *args, **kwargs):
    """Вызов API с паузой после запроса и ожиданием при FloodWait. None — при неудаче."""
    for _ in range(MAX_RETRIES):
        try:
            result = await func(*args, **kwargs)
        except errors.FloodWaitError as error:
            wait = error.seconds + 1
            print(f"    FloodWait: жду {wait} с")
            await asyncio.sleep(wait)
            continue
        except errors.RPCError as error:
            print(f"    Ошибка API: {type(error).__name__}")
            await asyncio.sleep(REQUEST_DELAY)
            return None
        await asyncio.sleep(REQUEST_DELAY)
        return result
    return None


def is_public_channel(chat) -> bool:
    return (
        isinstance(chat, types.Channel)
        and chat.broadcast
        and bool(chat.username)
        and not chat.scam
        and not chat.fake
    )


def is_russian(text: str) -> bool:
    letters = re.findall(r"[^\W\d_]", text)
    cyrillic = re.findall(r"[а-яёА-ЯЁ]", text)
    if len(cyrillic) < 5:
        return False
    return len(cyrillic) / len(letters) >= MIN_CYRILLIC_SHARE


def extract_contacts(about: str, own_username: str) -> list[str]:
    own = own_username.lower()
    contacts: list[str] = []
    seen: set[str] = set()

    def add(label: str, key: str) -> None:
        if key.lower() == own or key.lower() in seen:
            return
        seen.add(key.lower())
        contacts.append(label)

    for match in TME_LINK_RE.finditer(about):
        key = match.group(1)
        label = f"@{key}" if re.fullmatch(r"[A-Za-z][A-Za-z0-9_]+", key) else f"https://t.me/{key}"
        add(label, key)
    for match in MENTION_RE.finditer(about):
        add(f"@{match.group(1)}", match.group(1))

    return contacts


async def collect_candidates(client: TelegramClient) -> dict[int, types.Channel]:
    candidates: dict[int, types.Channel] = {}
    for index, keyword in enumerate(KEYWORDS, 1):
        print(f"[поиск {index}/{len(KEYWORDS)}] «{keyword}»")
        result = await safe_call(client, functions.contacts.SearchRequest(q=keyword, limit=SEARCH_LIMIT))
        if result is None:
            continue
        found = [chat for chat in result.chats if is_public_channel(chat)]
        new = [chat for chat in found if chat.id not in candidates]
        candidates.update({chat.id: chat for chat in new})
        print(f"    каналов: {len(found)}, новых: {len(new)}, всего кандидатов: {len(candidates)}")
    return candidates


async def build_row(client: TelegramClient, chat: types.Channel) -> dict | None:
    full = await safe_call(client, functions.channels.GetFullChannelRequest(chat))
    if full is None:
        return None

    subscribers = full.full_chat.participants_count or 0
    if not MIN_SUBSCRIBERS <= subscribers <= MAX_SUBSCRIBERS:
        print(f"    пропуск: подписчиков {subscribers}")
        return None

    about = (full.full_chat.about or "").strip()
    if not is_russian(f"{chat.title} {about}"):
        print("    пропуск: не русскоязычный")
        return None

    contacts = extract_contacts(about, chat.username)
    if REQUIRE_CONTACT and not contacts:
        print("    пропуск: нет контактов")
        return None

    messages = await safe_call(client.get_messages, chat, limit=1)
    if not messages:
        print("    пропуск: нет постов или канал недоступен")
        return None
    if messages[0].date < datetime.now(timezone.utc) - timedelta(days=MAX_POST_AGE_DAYS):
        print(f"    пропуск: последний пост {messages[0].date:%Y-%m-%d}")
        return None

    return {
        "Название канала": chat.title,
        "Ссылка на канал": f"https://t.me/{chat.username}",
        "Подписчики": subscribers,
        "Контакт админа": ", ".join(contacts),
        "Описание канала": " ".join(about.split()),
    }


def save_csv(rows: list[dict]) -> None:
    with open(OUTPUT_FILE, "w", encoding="utf-8-sig", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=CSV_COLUMNS)
        writer.writeheader()
        writer.writerows(rows)
    print(f"Сохранено каналов: {len(rows)} → {OUTPUT_FILE}")


async def main() -> None:
    api_id, api_hash = load_credentials()
    rows: list[dict] = []

    async with TelegramClient(SESSION_FILE, api_id, api_hash) as client:
        try:
            candidates = await collect_candidates(client)
            total = len(candidates)
            for index, chat in enumerate(candidates.values(), 1):
                print(f"[проверка {index}/{total}] @{chat.username}")
                row = await build_row(client, chat)
                if row:
                    rows.append(row)
                    print(f"    ✔ подходит ({row['Подписчики']} подп.)")
        finally:
            save_csv(rows)


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("\nПрервано пользователем.")
