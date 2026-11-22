"""Сбор базы живых русскоязычных Telegram-каналов для партнёрства и розыгрышей.

Telegram API и api_id не нужны: кандидаты берутся из поисковой выдачи (ddgs)
и открытых GitHub-подборок, качество проверяется по публичной веб-версии
канала https://t.me/s/<username>. Готовый CSV отправляется боту в Telegram.
"""
from __future__ import annotations

import csv
import os
import re
import time
from datetime import datetime, timedelta, timezone
from pathlib import Path
from urllib.parse import quote, unquote

import requests
from bs4 import BeautifulSoup

try:
    from ddgs import DDGS
except ImportError:  # старый пакет, если ddgs ещё не установлен
    from duckduckgo_search import DDGS

BASE_DIR = Path(__file__).resolve().parent
OUTPUT_FILE = BASE_DIR / "channels.csv"
ENV_FILE = BASE_DIR / ".env"

# ---------- Что искать ----------

SEARCH_QUERIES = [
    "site:t.me/s/ нейросети",
    "site:t.me/s/ искусственный интеллект",
    "site:t.me/s/ claude",
    "site:t.me/s/ вайбкодинг",
    "site:t.me/s/ программирование",
    "site:t.me/s/ фриланс",
    "site:t.me/s/ маркетинг",
    "site:t.me/s/ стартапы",
    "site:tgstat.ru/channel нейросети",
    "site:tgstat.ru/channel программирование",
    "site:tgstat.ru/channel маркетинг",
]
RESULTS_PER_QUERY = 50

# Известные подборки — их читаем напрямую из raw.
GITHUB_RAW_LISTS = [
    "https://raw.githubusercontent.com/abvc/everything-about-telegram-channels/main/README.md",
    "https://raw.githubusercontent.com/GooKv/awesome-telegram-channels/main/README.md",
    "https://raw.githubusercontent.com/sindresorhus/awesome/main/readme.md",
    "https://raw.githubusercontent.com/maxmouchet/awesome-telegram/main/README.md",
]
GITHUB_REPO_QUERIES = [
    "awesome telegram channels",
    "telegram channels list russian",
    "awesome russian telegram",
    "telegram catalog channels",
]
GITHUB_REPOS_PER_QUERY = 10

# ---------- Пороги отбора ----------

MIN_SUBSCRIBERS = 500
MAX_SUBSCRIBERS = 25_000
MAX_POST_AGE_DAYS = 10
MIN_ENGAGEMENT_PCT = 12.0     # средние просмотры / подписчики, %
POSTS_FOR_ENGAGEMENT = 5      # по скольким последним постам считаем просмотры
MIN_CYRILLIC_SHARE = 0.3      # доля кириллицы среди букв в названии + описании
REQUEST_DELAY = 2.0           # секунд после каждого запроса к t.me
TIMEOUT = 25
MAX_RETRIES = 3

# Сколько каналов максимум проверять за прогон (None — без ограничения).
MAX_CHECKS: int | None = None

CSV_COLUMNS = [
    "Название канала",
    "Ссылка на канал",
    "Подписчики",
    "Средние просмотры",
    "Вовлечённость %",
    "Контакт админа",
    "Описание канала",
]

USERNAME_PART = r"[A-Za-z][A-Za-z0-9_]{4,31}"

TME_RE = re.compile(rf"t\.me/(?:s/)?({USERNAME_PART})")
MENTION_RE = re.compile(rf"(?<![\w/@.])@({USERNAME_PART.with_args if False else USERNAME_PART})\b")
TGSTAT_RE = re.compile(rf"tgstat\.ru/(?:channel|chat)/@?({USERNAME_PART})")
COUNT_RE = re.compile(r"([\d\s.,]+)\s*(k|m|тыс\.?|млн\.?)?")

# Служебные пути Telegram, которые не являются каналами.
RESERVED = {
    "s", "joinchat", "addstickers", "share", "proxy", "socks", "iv",
    "setlanguage", "login", "durov", "telegram", "telegramtips", "contact",
}

SESSION = requests.Session()
SESSION.headers.update({
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
        "(KHTML, like Gecko) Chrome/122.0 Safari/537.36"
    ),
    "Accept-Language": "ru-RU,ru;q=0.9,en;q=0.7",
})


# ---------- .env ----------

def load_env(path: Path = ENV_FILE) -> None:
    """Подхватывает .env в переменные окружения, не перетирая уже заданные."""
    if not path.is_file():
        return
    for raw_line in path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if line == "" or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        key = key.strip()
        value = value.strip().strip('"').strip("'")
        os.environ.setdefault(key, value)


load_env()

BOT_TOKEN = os.environ.get("BOT_TOKEN", "").strip()
ADMIN_CHAT_ID = os.environ.get("ADMIN_CHAT_ID", "").strip()
GITHUB_TOKEN = os.environ.get("GITHUB_TOKEN", "").strip()


# ---------- Сеть ----------

def fetch(url: str, *, delay: float = REQUEST_DELAY, headers: dict | None = None,
          attempts: int = MAX_RETRIES) -> str | None:
    """GET с паузами и повторами. None, если страница недоступна."""
    for attempt in range(1, attempts + 1):
        try:
            response = SESSION.get(url, timeout=TIMEOUT, headers=headers or {})
        except requests.RequestException as error:
            print(f"    сеть: {type(error).__name__}, повтор {attempt}/{attempts}")
            time.sleep(delay * attempt)
            continue

        if delay > 0:
            time.sleep(delay)

        if response.status_code == 200:
            return response.text

        retryable = response.status_code == 429 or response.status_code >= 500
        print(f"    HTTP {response.status_code}" + (", повтор" if retryable else ""))
        if not retryable:
            return None
        time.sleep(delay * attempt)

    return None


def get_soup(url: str) -> BeautifulSoup | None:
    html = fetch(url)
    return BeautifulSoup(html, "html.parser") if html else None


# ---------- Разбор чисел и текста ----------

def parse_count(text: str) -> int:
    """«1 234», «12.3K», «1,2 тыс.» → целое число."""
    cleaned = text.replace("\xa0", " ").replace("\u2009", " ").strip().lower()
    match = COUNT_RE.search(cleaned)
    if match is None:
        return 0

    number = match.group(1).replace(" ", "")
    number = number.replace(",", "") if "," in number and "." in number else number.replace(",", ".")

    try:
        value = float(number)
    except ValueError:
        return 0

    suffix = (match.group(2) or "").rstrip(".")
    if suffix in {"k", "тыс"}:
        value *= 1_000
    elif suffix in {"m", "млн"}:
        value *= 1_000_000

    return int(round(value))


def is_russian(text: str) -> bool:
    letters = re.findall(r"[^\W\d_]", text)
    cyrillic = re.findall(r"[а-яёА-ЯЁ]", text)
    if len(letters) < 5 or len(cyrillic) < 5:
        return False
    return len(cyrillic) / len(letters) >= MIN_CYRILLIC_SHARE


def extract_usernames(text: str) -> set[str]:
    """Все упоминания каналов в тексте: t.me/..., tgstat.ru/channel/..."""
    decoded = unquote(text)
    names: set[str] = set()

    for pattern in (TME_RE, TGSTAT_RE):
        for match in pattern.finditer(decoded):
            name = match.group(1)
            if name.lower() in RESERVED or name.isdigit():
                continue
            names.add(name)

    return names


def extract_contacts(description: str, own_username: str) -> list[str]:
    """@username админов из описания канала, кроме самого канала."""
    own = own_username.lower()
    contacts: list[str] = []
    seen: set[str] = set()

    for match in MENTION_RE.finditer(description):
        name = match.group(1)
        if name.lower() == own or name.lower() in seen or name.lower() in RESERVED:
            continue
        seen.add(name.lower())
        contacts.append(f"@{name}")

    for name in sorted(extract_usernames(description)):
        if name.lower() == own or name.lower() in seen:
            continue
        seen.add(name.lower())
        contacts.append(f"@{name}")

    return contacts


# ---------- Источник 1: поисковая выдача ----------

def search_usernames() -> set[str]:
    names: set[str] = set()
    print(f"\n[поиск] запросов: {len(SEARCH_QUERIES)}")

    with DDGS() as ddgs:
        for index, query in enumerate(SEARCH_QUERIES, 1):
            print(f"[{index}/{len(SEARCH_QUERIES)}] {query}")
            try:
                try:
                    results = ddgs.text(query, region="ru-ru", max_results=RESULTS_PER_QUERY)
                except TypeError:
                    results = ddgs.text(query, max_results=RESULTS_PER_QUERY)
            except Exception as error:
                print(f"    поиск не удался: {type(error).__name__}: {error}")
                time.sleep(REQUEST_DELAY)
                continue

            found: set[str] = set()
            for result in results or []:
                blob = " ".join(str(result.get(key, "")) for key in ("href", "title", "body"))
                found |= extract_usernames(blob)
                body = str(result.get("body", ""))
                found |= {name for name in re.findall(rf"(?<![\w/@.])@({USERNAME_PART})\b", body)
                          if name.lower() not in RESERVED}

            names |= found
            print(f"    результатов {len(results or [])}, новых каналов {len(found)}")
            time.sleep(REQUEST_DELAY)

    return names


# ---------- Источник 2: GitHub-подборки ----------

def github_headers() -> dict:
    headers = {"Accept": "application/vnd.github+json"}
    if GITHUB_TOKEN:
        headers["Authorization"] = f"Bearer {GITHUB_TOKEN}"
    return headers


def github_readmes() -> list[str]:
    """Ссылки на README подборок: известные списки + результаты поиска репозиториев."""
    urls = list(GITHUB_RAW_LISTS)

    for query in GITHUB_REPO_QUERIES:
        api = (
            "https://api.github.com/search/repositories?q="
            + quote(query, safe="") + f"&sort=stars&per_page={GITHUB_REPOS_PER_QUERY}"
        )
        print(f"[github] {query}")
        raw = fetch(api, delay=1.0, headers=github_headers(), attempts=2)
        if raw is None:
            continue

        try:
            import json
            items = json.loads(raw).get("items", [])
        except ValueError:
            print("    ответ не JSON")
            continue

        for repo in items:
            full_name = repo.get("full_name", "")
            branch = repo.get("default_branch", "main")
            if not full_name:
                continue
            for name in ("README.md", "readme.md", "README.MD"):
                urls.append(f"https://raw.githubusercontent.com/{full_name}/{branch}/{name}")

    return urls


def github_usernames() -> set[str]:
    names: set[str] = set()
    readmes = github_readmes()
    print(f"\n[github] подборок к чтению: {len(readmes)}")

    for index, url in enumerate(readmes, 1):
        html = fetch(url, delay=0.5, headers=github_headers(), attempts=2)
        if html is None:
            continue
        found = extract_usernames(html)
        names |= found
        if found:
            print(f"[{index}/{len(readmes)}] {url.rpartition('/')[0]}: каналов {len(found)}")

    return names


# ---------- Проверка канала ----------

def parse_subscribers(info: BeautifulSoup) -> int | None:
    for counter in info.select("div.tgme_channel_info_counter"):
        label_el = counter.select_one("span.counter_type")
        if label_el is None:
            continue
        label = label_el.get_text(" ", strip=True).lower()
        if "subscriber" in label or "подписчик" in label:
            value_el = counter.select_one("span.counter_value")
            return parse_count(value_el.get_text() if value_el else "")
    return None


def parse_posts(soup: BeautifulSoup) -> list[dict]:
    posts: list[dict] = []

    for message in soup.select("div.tgme_widget_message"):
        time_el = message.select_one("time[datetime]")
        if time_el is None or not time_el.get("datetime"):
            continue

        try:
            date = datetime.fromisoformat(time_el["datetime"])
        except ValueError:
            continue
        if date.tzinfo is None:
            date = date.replace(tzinfo=timezone.utc)

        views_el = message.select_one("span.tgme_widget_message_views")
        views = parse_count(views_el.get_text()) if views_el else 0
        posts.append({"date": date, "views": views})

    posts.sort(key=lambda post: post["date"])
    return posts


def check_channel(username: str) -> dict | None:
    soup = get_soup(f"https://t.me/s/{username}")
    if soup is None:
        return None

    # Блок tgme_channel_info есть только у публичных каналов, у групп его нет.
    info = soup.select_one("div.tgme_channel_info")
    if info is None:
        print("    пропуск: не публичный канал")
        return None

    title_el = info.select_one(".tgme_channel_info_header_title")
    title = title_el.get_text(" ", strip=True) if title_el else username

    subscribers = parse_subscribers(info)
    if subscribers is None:
        print("    пропуск: подписчики скрыты")
        return None
    if not MIN_SUBSCRIBERS <= subscribers <= MAX_SUBSCRIBERS:
        print(f"    пропуск: подписчиков {subscribers}")
        return None

    description_el = info.select_one(".tgme_channel_info_description")
    description = description_el.get_text(" ", strip=True) if description_el else ""
    description = " ".join(description.split())

    if not is_russian(f"{title} {description}"):
        print("    пропуск: не русскоязычный")
        return None

    posts = parse_posts(soup)
    if not posts:
        print("    пропуск: нет постов в веб-версии")
        return None

    last_post = posts[-1]["date"]
    if last_post < datetime.now(timezone.utc) - timedelta(days=MAX_POST_AGE_DAYS):
        print(f"    пропуск: последний пост {last_post:%d.%m.%Y}")
        return None

    views = [post["views"] for post in posts if post["views"] > 0][-POSTS_FOR_ENGAGEMENT:]
    if not views:
        print("    пропуск: просмотры скрыты")
        return None

    average_views = sum(views) / len(views)
    engagement = average_views / subscribers * 100
    if engagement < MIN_ENGAGEMENT_PCT:
        print(f"    пропуск: вовлечённость {engagement:.1f}%")
        return None

    return {
        "Название канала": title,
        "Ссылка на канал": f"https://t.me/{username}",
        "Подписчики": subscribers,
        "Средние просмотры": int(round(average_views)),
        "Вовлечённость %": round(engagement, 1),
        "Контакт админа": ", ".join(extract_contacts(description, username)),
        "Описание канала": description,
    }


# ---------- Результат ----------

def save_csv(rows: list[dict]) -> None:
    with open(OUTPUT_FILE, "w", encoding="utf-8-sig", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=CSV_COLUMNS)
        writer.writeheader()
        writer.writerows(rows)
    print(f"Сохранено каналов: {len(rows)} → {OUTPUT_FILE}")


def send_to_telegram(rows: list[dict]) -> None:
    if not BOT_TOKEN or not ADMIN_CHAT_ID:
        print("Отправка в Telegram пропущена: нет BOT_TOKEN или ADMIN_CHAT_ID в .env")
        return
    if not OUTPUT_FILE.is_file():
        print("Отправка в Telegram пропущена: файл channels.csv не создан")
        return

    average = sum(row["Вовлечённость %"] for row in rows) / len(rows) if rows else 0
    caption = (
        f"Каналов в базе: {len(rows)}\n"
        f"Средняя вовлечённость: {average:.1f}%\n"
        f"Отбор: {MIN_SUBSCRIBERS}–{MAX_SUBSCRIBERS} подписчиков, "
        f"пост не старше {MAX_POST_AGE_DAYS} дней, просмотры ≥ {MIN_ENGAGEMENT_PCT:.0f}%"
    )

    try:
        with open(OUTPUT_FILE, "rb") as document:
            response = SESSION.post(
                f"https://api.telegram.org/bot{BOT_TOKEN}/sendDocument",
                data={"chat_id": ADMIN_CHAT_ID, "caption": caption[:1024]},
                files={"document": (OUTPUT_FILE.name, document, "text/csv")},
                timeout=120,
            )
    except requests.RequestException as error:
        print(f"Отправка не удалась: {type(error).__name__}: {error}")
        return

    if response.status_code != 200:
        print(f"Отправка не удалась: HTTP {response.status_code} {response.text[:200]}")
        return

    print(f"Файл отправлен в Telegram (chat_id {ADMIN_CHAT_ID}).")


def main() -> None:
    rows: list[dict] = []

    try:
        candidates = search_usernames() | github_usernames()
        ordered = sorted(candidates, key=str.lower)
        if MAX_CHECKS is not None:
            ordered = ordered[:MAX_CHECKS]

        total = len(ordered)
        if total == 0:
            print("Кандидаты не найдены: поисковик и GitHub не отдали ссылки.")
            return

        print(f"\nУникальных кандидатов на проверку: {total}\n")

        for index, username in enumerate(ordered, 1):
            print(f"[проверка {index}/{total}] @{username}")
            row = check_channel(username)
            if row is not None:
                rows.append(row)
                print(f"    ✔ подходит: вовлечённость {row['Вовлечённость %']}%")
    except KeyboardInterrupt:
        print("\nПрервано пользователем, сохраняю найденное.")
    finally:
        rows.sort(key=lambda row: row["Вовлечённость %"], reverse=True)
        if rows:
            save_csv(rows)
            send_to_telegram(rows)
        else:
            print("Подходящих каналов не найдено.")


if __name__ == "__main__":
    main()