"""Сбор двух баз русскоязычных Telegram-каналов для партнёрства.

channels_outreach.csv — 150..15 000 подписчиков, обязательный контакт админа.
channels_comments.csv — 3 000..200 000 подписчиков, крупные AI/tech-каналы.

Telegram API не нужен: кандидаты берутся из выдачи ddgs, качество
проверяется по публичной веб-версии https://t.me/s/<username>.
"""
from __future__ import annotations

import csv
import re
import time
from datetime import datetime, timedelta, timezone
from pathlib import Path
from urllib.parse import unquote

import requests
from bs4 import BeautifulSoup

try:
    from ddgs import DDGS
except ImportError:  # старый пакет, если ddgs ещё не установлен
    from duckduckgo_search import DDGS

BASE_DIR = Path(__file__).resolve().parent
OUTREACH_FILE = BASE_DIR / "channels_outreach.csv"
COMMENTS_FILE = BASE_DIR / "channels_comments.csv"

# ---------- Что искать ----------

SEARCH_QUERIES = [
    "site:t.me/s/ нейросети",
    "site:t.me нейросети",
    "site:t.me/s/ искусственный интеллект",
    "site:t.me искусственный интеллект",
    "site:t.me/s/ вайбкодинг",
    "site:t.me вайбкодинг",
    "site:t.me/s/ claude",
    "site:t.me claude код",
    "site:t.me/s/ cursor",
    "site:t.me chatgpt",
    "site:t.me midjourney",
    "site:t.me промпты",
    "site:t.me нейросети для бизнеса",
    "site:t.me автоматизация бизнеса",
    "site:t.me боты без кода",
    "site:t.me создание сайтов на нейросетях",
    "site:t.me фриланс нейросети",
    "site:t.me заработок на нейросетях",
    "site:t.me инди разработчик",
    "site:t.me соло разработчик",
    "site:t.me инди мейкер",
    "site:t.me n8n",
    "site:t.me no-code",
    "site:t.me маркетинг нейросети",
    "site:t.me/s/ программирование",
    "site:t.me/s/ фриланс",
    "site:t.me/s/ маркетинг",
    "site:t.me/s/ стартапы",
    "site:tgstat.ru/channel нейросети",
    "site:tgstat.ru/channel вайбкодинг",
    "site:tgstat.ru/channel программирование",
]
RESULTS_PER_QUERY = 50

# ---------- Пороги отбора ----------

OUTREACH_MIN_SUBSCRIBERS = 150
OUTREACH_MAX_SUBSCRIBERS = 15_000
COMMENTS_MIN_SUBSCRIBERS = 3_000
COMMENTS_MAX_SUBSCRIBERS = 200_000
MAX_POST_AGE_DAYS = 10
MIN_CYRILLIC_SHARE = 0.3
REQUEST_DELAY = 1.8          # секунд после каждого запроса к t.me
TIMEOUT = 25
MAX_RETRIES = 3
POSTS_FOR_VIEWS = 5          # по скольким последним постам считаем просмотры

OUTREACH_TARGET = 60         # сколько каналов набрать в базу рассылки
COMMENTS_TARGET = 40         # сколько каналов набрать в базу для комментариев
MAX_CHECKS = 400             # страховка от бесконечного прогона

CSV_COLUMNS = [
    "Название канала",
    "Ссылка на канал",
    "Подписчики",
    "Средние просмотры",
    "Вовлечённость %",
    "Контакт админа",
    "Описание канала",
]

# ---------- Регулярки ----------

USERNAME_PART = r"[A-Za-z][A-Za-z0-9_]{4,31}"
TME_RE = re.compile(rf"t\.me/(?:s/)?({USERNAME_PART})")
TGSTAT_RE = re.compile(rf"tgstat\.ru/(?:channel|chat)/@?({USERNAME_PART})")
CONTACT_RE = re.compile(r"(?<![\w/@.])@([A-Za-z][A-Za-z0-9_]{3,31})\b")
COUNT_RE = re.compile(r"([\d\s.,]+)\s*(k|m|тыс\.?|млн\.?)?")

KZ_RE = re.compile(r"\.kz\b|казахстан|астан[аеы]|алмат[ыае]|казах|караганда|шымкент", re.I)
VACANCY_RE = re.compile(r"ваканси|резюме|hh\.ru|ищем\s+сотрудник|работа\s+в\s+|подработк", re.I)
NEWS_RE = re.compile(r"новост|news|дайджест", re.I)

OUTREACH_RE = re.compile(
    r"вайб[- ]?код|vibe[- ]?cod|нейро|нейронк|\bии\b|\bai\b|chatgpt|\bgpt\b|claude|midjourney"
    r"|промпт|prompt|no[- ]?code|low[- ]?code|n8n|zapier|cursor|tilda|\bбот[а-яё]*|сайт[а-яё]*"
    r"|лендинг|автоматизац|фриланс|заработ[а-яё]*|доход|инди|соло[- ]?разработ|мейкер|стартап"
    r"|маркетинг|\bsmm\b|продвижен|воронк|python|разработ[а-яё]*|программирован",
    re.I,
)

COMMENTS_RE = re.compile(
    r"нейро|нейронк|\bии\b|\bai\b|искусственн\w*\s+интеллект|chatgpt|\bgpt\b|claude|midjourney"
    r"|вайб[- ]?код|vibe[- ]?cod|\bcursor\b|\bllm\b|промпт|prompt|no[- ]?code|автоматизац"
    r"|технолог|разработ|программирован|digital|\bит\b",
    re.I,
)

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


# ---------- Сеть ----------

def fetch(url: str, *, delay: float = REQUEST_DELAY, attempts: int = MAX_RETRIES) -> str | None:
    """GET с паузой и повторами. None, если страница недоступна."""
    for attempt in range(1, attempts + 1):
        try:
            response = SESSION.get(url, timeout=TIMEOUT)
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


# ---------- Разбор текста и чисел ----------

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
    """@username из описания канала, кроме самого канала."""
    own = own_username.lower()
    contacts: list[str] = []
    seen: set[str] = set()

    for match in CONTACT_RE.finditer(description):
        name = match.group(1)
        if name.lower() == own or name.lower() in seen or name.lower() in RESERVED:
            continue
        seen.add(name.lower())
        contacts.append(f"@{name}")

    return contacts


# ---------- Источник кандидатов ----------

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
                results = list(results or [])
            except Exception as error:
                print(f"    поиск не удался: {type(error).__name__}: {error}")
                time.sleep(REQUEST_DELAY)
                continue

            found: set[str] = set()
            for result in results:
                blob = " ".join(str(result.get(key, "")) for key in ("href", "title", "body"))
                found |= extract_usernames(blob)
                body = str(result.get("body", ""))
                found |= {
                    name for name in CONTACT_RE.findall(body)
                    if name.lower() not in RESERVED
                }

            names |= found
            print(f"    результатов {len(results)}, новых каналов {len(found)}")
            time.sleep(REQUEST_DELAY)

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


def inspect_channel(username: str) -> dict | None:
    """Забирает из веб-превью всё, что нужно для отбора. None — если это не канал."""
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

    description_el = info.select_one(".tgme_channel_info_description")
    description = description_el.get_text(" ", strip=True) if description_el else ""
    description = " ".join(description.split())

    posts = parse_posts(soup)
    if not posts:
        print("    пропуск: нет постов в веб-версии")
        return None

    views = [post["views"] for post in posts if post["views"] > 0][-POSTS_FOR_VIEWS:]
    average_views = sum(views) / len(views) if views else 0

    return {
        "username": username,
        "title": title,
        "subscribers": subscribers,
        "average_views": int(round(average_views)),
        "engagement": round(average_views / subscribers * 100, 1) if subscribers else 0.0,
        "contacts": extract_contacts(description, username),
        "description": description,
        "last_post": posts[-1]["date"],
        "text": f"{title} {description}",
    }


def classify(channel: dict) -> str | None:
    """Куда положить канал: outreach, comments или никуда."""
    text = channel["text"]

    if not is_russian(text):
        print("    пропуск: не русскоязычный")
        return None
    if KZ_RE.search(text):
        print("    пропуск: не российский канал")
        return None

    cutoff = datetime.now(timezone.utc) - timedelta(days=MAX_POST_AGE_DAYS)
    if channel["last_post"] < cutoff:
        print(f"    пропуск: последний пост {channel['last_post']:%d.%m.%Y}")
        return None

    subscribers = channel["subscribers"]
    if (OUTREACH_MIN_SUBSCRIBERS <= subscribers <= OUTREACH_MAX_SUBSCRIBERS
            and channel["contacts"]
            and OUTREACH_RE.search(text)
            and not VACANCY_RE.search(text)
            and not NEWS_RE.search(channel["title"])):
        return "outreach"

    if (COMMENTS_MIN_SUBSCRIBERS <= subscribers <= COMMENTS_MAX_SUBSCRIBERS
            and COMMENTS_RE.search(text)):
        return "comments"

    print("    пропуск: не подходит ни в одну базу")
    return None


# ---------- Сохранение ----------

def ensure_csv(path: Path) -> None:
    if path.is_file() and path.stat().st_size > 0:
        return
    with open(path, "w", encoding="utf-8-sig", newline="") as file:
        csv.DictWriter(file, fieldnames=CSV_COLUMNS).writeheader()


def append_row(path: Path, row: dict) -> None:
    ensure_csv(path)
    with open(path, "a", encoding="utf-8", newline="") as file:
        csv.DictWriter(file, fieldnames=CSV_COLUMNS).writerow(row)


def build_row(channel: dict) -> dict:
    return {
        "Название канала": channel["title"],
        "Ссылка на канал": f"https://t.me/{channel['username']}",
        "Подписчики": channel["subscribers"],
        "Средние просмотры": channel["average_views"],
        "Вовлечённость %": channel["engagement"],
        "Контакт админа": ", ".join(channel["contacts"]),
        "Описание канала": channel["description"],
    }


def sort_csv(path: Path) -> int:
    """Пересортировывает готовый файл по убыванию вовлечённости. Возвращает число строк."""
    if not path.is_file():
        return 0
    with open(path, encoding="utf-8-sig", newline="") as file:
        rows = list(csv.DictReader(file))
    if not rows:
        return 0

    rows.sort(key=lambda row: float(row["Вовлечённость %"]), reverse=True)
    with open(path, "w", encoding="utf-8-sig", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=CSV_COLUMNS)
        writer.writeheader()
        writer.writerows(rows)
    return len(rows)


def main() -> None:
    files = {"outreach": OUTREACH_FILE, "comments": COMMENTS_FILE}
    targets = {"outreach": OUTREACH_TARGET, "comments": COMMENTS_TARGET}
    counts = {"outreach": 0, "comments": 0}

    for path in files.values():
        path.unlink(missing_ok=True)

    try:
        candidates = sorted(search_usernames(), key=str.lower)
        total = len(candidates)
        if total == 0:
            print("Кандидаты не найдены: поисковик не отдал ни одной ссылки.")
            return

        print(f"\nКандидатов на проверку: {total}\n")

        for index, username in enumerate(candidates[:MAX_CHECKS], 1):
            if all(counts[kind] >= targets[kind] for kind in targets):
                print("Набраны обе базы, останавливаюсь.")
                break

            print(f"[проверка {index}/{min(total, MAX_CHECKS)}] @{username} "
                  f"(outreach {counts['outreach']}, comments {counts['comments']})")
            channel = inspect_channel(username)
            if channel is None:
                continue

            kind = classify(channel)
            if kind is None:
                continue

            append_row(files[kind], build_row(channel))
            counts[kind] += 1
            print(f"    ✔ {kind}: {channel['subscribers']} подписчиков, "
                  f"вовлечённость {channel['engagement']}%")
    except KeyboardInterrupt:
        print("\nПрервано пользователем, оставляю уже найденное.")
    finally:
        for kind, path in files.items():
            saved = sort_csv(path)
            print(f"{path.name}: каналов {saved}")


if __name__ == "__main__":
    main()