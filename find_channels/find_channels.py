"""Сбор базы живых русскоязычных Telegram-каналов для партнёрства и розыгрышей.

Работает без Telegram API и api_id: кандидаты берутся из открытых каталогов
и поисковой выдачи, качество проверяется по публичной веб-версии канала.
"""
from __future__ import annotations

import csv
import re
import time
from datetime import datetime, timedelta, timezone
from pathlib import Path
from urllib.parse import quote, unquote

import requests
from bs4 import BeautifulSoup

BASE_DIR = Path(__file__).resolve().parent
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
MAX_SUBSCRIBERS = 25_000
MAX_POST_AGE_DAYS = 7
MIN_ENGAGEMENT_PCT = 15.0     # средние просмотры / подписчики, %
POSTS_FOR_ENGAGEMENT = 5      # по скольким последним постам считаем просмотры
MIN_CYRILLIC_SHARE = 0.3      # доля кириллицы среди букв в названии + описании
REQUEST_DELAY = 2.0           # секунд после каждого запроса
TIMEOUT = 25
MAX_RETRIES = 3

CSV_COLUMNS = [
    "Название канала",
    "Ссылка на канал",
    "Подписчики",
    "Средние просмотры",
    "Вовлечённость %",
    "Контакт админа",
    "Описание канала",
]

SESSION = requests.Session()
SESSION.headers.update({
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
        "(KHTML, like Gecko) Chrome/122.0 Safari/537.36"
    ),
    "Accept-Language": "en-US,en;q=0.9",
})

TME_RE = re.compile(r"t\.me/(?:s/)?([A-Za-z][A-Za-z0-9_]{4,31})")
TLGRM_RE = re.compile(r"tlgrm\.ru/channels/@([A-Za-z][A-Za-z0-9_]{4,31})")
MENTION_RE = re.compile(r"(?<![\w/@.])@([A-Za-z][A-Za-z0-9_]{3,31})\b")
COUNT_RE = re.compile(r"([\d\s.,]+)\s*(k|m|тыс\.?|млн\.?)?")

# Служебные пути Telegram, которые не являются каналами.
RESERVED = {
    "s", "joinchat", "addstickers", "share", "proxy", "socks",
    "iv", "setlanguage", "login", "durov", "telegram",
}


def fetch(url: str) -> str | None:
    """GET с паузами и повторами. None, если страница недоступна."""
    for attempt in range(1, MAX_RETRIES + 1):
        try:
            response = SESSION.get(url, timeout=TIMEOUT)
        except requests.RequestException as error:
            print(f"    сеть: {type(error).__name__}, повтор {attempt}/{MAX_RETRIES}")
            time.sleep(REQUEST_DELAY * attempt)
            continue

        time.sleep(REQUEST_DELAY)

        if response.status_code == 200:
            return response.text

        retryable = response.status_code == 429 or response.status_code >= 500
        print(f"    HTTP {response.status_code}" + (", повтор" if retryable else ""))
        if not retryable:
            return None
        time.sleep(REQUEST_DELAY * attempt)
    return None


def get_soup(url: str) -> BeautifulSoup | None:
    html = fetch(url)
    return BeautifulSoup(html, "html.parser") if html else None


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


def extract_contacts(description: str, own_username: str) -> list[str]:
    own = own_username.lower()
    contacts: list[str] = []
    seen: set[str] = set()

    for match in MENTION_RE.finditer(description):
        name = match.group(1)
        if name.lower() == own or name.lower() in seen:
            continue
        seen.add(name.lower())
        contacts.append(f"@{name}")

    return contacts


def catalog_links(keyword: str) -> list[tuple[str, str]]:
    plain = quote(keyword, safe="")
    site_query = quote(f"site:t.me {keyword}", safe="")
    return [
        ("tlgrm.ru", f"https://tlgrm.ru/search?q={plain}"),
        ("telega.in", f"https://telega.in/catalog?q={plain}"),
        ("duckduckgo", f"https://html.duckduckgo.com/html/?q={site_query}"),
        ("ddg-lite", f"https://lite.duckduckgo.com/lite/?q={site_query}"),
    ]


def extract_usernames(html: str) -> set[str]:
    # unquote разворачивает редиректные ссылки поисковиков в прямые t.me/...
    text = unquote(html)
    names: set[str] = set()

    for pattern in (TME_RE, TLGRM_RE):
        for match in pattern.finditer(text):
            name = match.group(1)
            if name.lower() in RESERVED:
                continue
            names.add(name)

    return names


def collect_candidates() -> list[str]:
    found: dict[str, None] = {}

    for index, keyword in enumerate(KEYWORDS, 1):
        print(f"[поиск {index}/{len(KEYWORDS)}] «{keyword}»")
        before = len(found)

        for source, url in catalog_links(keyword):
            html = fetch(url)
            if html is None:
                print(f"    {source}: недоступен")
                continue
            names = extract_usernames(html)
            for name in names:
                found.setdefault(name, None)
            print(f"    {source}: ссылок {len(names)}")

        print(f"    новых: {len(found) - before}, всего кандидатов: {len(found)}")

    return list(found)


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


def save_csv(rows: list[dict]) -> None:
    with open(OUTPUT_FILE, "w", encoding="utf-8-sig", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=CSV_COLUMNS)
        writer.writeheader()
        writer.writerows(rows)
    print(f"Сохранено каналов: {len(rows)} → {OUTPUT_FILE}")


def main() -> None:
    rows: list[dict] = []

    try:
        candidates = collect_candidates()
        total = len(candidates)
        if total == 0:
            print("Кандидаты не найдены: каталоги и поисковики не отдали ссылки.")
            return

        print(f"\nКандидатов на проверку: {total}\n")

        for index, username in enumerate(candidates, 1):
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
        else:
            print("Подходящих каналов не найдено.")


if __name__ == "__main__":
    main()