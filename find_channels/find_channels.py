"""Ночной сбор базы живых российских Telegram-каналов.

Работает бесконечными раундами до Ctrl+C. Кандидаты берутся из поисковой
выдачи (ddgs), качество проверяется по https://t.me/s/<username>.
Проверенные каналы запоминаются, поэтому повторных проверок нет.
"""
from __future__ import annotations

import csv
import random
import re
import signal
import sys
import time
from datetime import datetime, timedelta, timezone
from pathlib import Path
from urllib.parse import unquote

import requests
from bs4 import BeautifulSoup

try:
    from ddgs import DDGS
except ImportError:  # запасной вариант, если ddgs ещё не установлен
    from duckduckgo_search import DDGS

BASE_DIR = Path(__file__).resolve().parent
OUTREACH_FILE = BASE_DIR / "channels_outreach.csv"
COMMENTS_FILE = BASE_DIR / "channels_comments.csv"
CHECKED_FILE = BASE_DIR / "checked_usernames.txt"

# ---------- Пул запросов ----------

SEARCH_QUERIES = [
    "site:t.me/s/ вайбкодинг",
    "site:t.me вайбкодинг без кода",
    "site:t.me/s/ вайб кодинг",
    "site:t.me/s/ cursor",
    "site:t.me cursor ide",
    "site:t.me/s/ claude code",
    "site:t.me/s/ claude",
    "site:t.me/s/ chatgpt",
    "site:t.me/s/ нейросети",
    "site:t.me/s/ искусственный интеллект",
    "site:t.me/s/ нейросети для бизнеса",
    "site:t.me/s/ нейросети для маркетинга",
    "site:t.me/s/ промпты",
    "site:t.me/s/ промпт инженерия",
    "site:t.me/s/ midjourney",
    "site:t.me/s/ генерация контента нейросети",
    "site:t.me/s/ автоматизация контента",
    "site:t.me/s/ автоматизация бизнеса",
    "site:t.me/s/ n8n",
    "site:t.me/s/ no-code",
    "site:t.me/s/ боты без кода",
    "site:t.me/s/ telegram mini apps",
    "site:t.me/s/ telegram боты",
    "site:t.me/s/ создание сайтов на нейросетях",
    "site:t.me/s/ заработок на нейросетях",
    "site:t.me/s/ заработок на ии",
    "site:t.me/s/ фриланс нейросети",
    "site:t.me/s/ фриланс на ии",
    "site:t.me/s/ инди разработчик",
    "site:t.me/s/ соло разработчик",
    "site:t.me/s/ инди мейкер",
    "site:t.me/s/ микросаас",
    "site:t.me/s/ маркетинг нейросети",
    "site:t.me/s/ продвижение с нейросетями",
    "site:t.me/s/ программирование",
    "site:tgstat.ru/channel нейросети",
    "site:tgstat.ru/channel вайбкодинг",
    "site:tgstat.ru/channel claude",
    "site:tgstat.ru/channel автоматизация",
    "site:tgstat.ru/channel программирование",
]
RESULTS_PER_QUERY = 50

# ---------- Режим работы ----------

ROUND_CHECK_LIMIT = 350          # кандидатов на раунд ≈ 15–20 минут
CHECK_PAUSE = (2.0, 3.0)         # пауза перед каждым t.me/s/<username>
QUERY_PAUSE = (3.0, 6.0)         # пауза между поисковыми запросами
ROUND_BREAK = (300, 600)         # перерыв между раундами, 5–10 минут
BLOCK_BREAK = 600                # пауза после серии ошибок поиска
MAX_CONSECUTIVE_ERRORS = 5

# ---------- Пороги ----------

SUB_RANGE_OUTREACH = (150, 15_000)
SUB_RANGE_COMMENTS = (3_000, 250_000)
MAX_POST_AGE_DAYS = 10
POSTS_FOR_ENGAGEMENT = 5
MIN_CYRILLIC_SHARE = 0.3
REQUEST_DELAY = 1.5
TIMEOUT = 25
MAX_RETRIES = 3

# ---------- Ниши ----------

NICHE_MARKERS: dict[str, tuple[str, ...]] = {
    "вайбкодинг": ("вайбкод", "vibe cod", "vibecod", "без кода", "no-code", "no code",
                   "low-code", "низкий код", "cursor", "claude code", "сайты на нейро"),
    "нейросети": ("нейросет", "нейронк", "искусственный интеллект", "gpt", "chatgpt",
                  "claude", "midjourney", "промпт", "prompt", "llm", "ai-агент", "ии-агент"),
    "заработок": ("заработ", "фриланс", "доход", "инди", "indie", "соло-разработ",
                  "соло разработ", "мейкер", "saas"),
    "разработка": ("разработ", "программ", "кодинг", "код ", "python", "javascript",
                   "mini app", "телеграм бот", "telegram bot", "боты без кода"),
    "маркетинг": ("маркетинг", "продвиж", "воронк", "таргет", "автоматизац",
                  "контент", "трафик"),
    "технологии": ("технолог", "it-", "стартап", "продукт", "цифров", "инновац"),
}

TECH_NICHES = {"вайбкодинг", "нейросети", "разработка", "технологии"}

KZ_MARKERS = (".kz", "казахстан", "алматы", "астана", "тенге", "🇰🇿")
VACANCY_MARKERS = ("ваканс", "ищу работу", "поиск работы", "резюме", "hh.ru",
                   "подборка вакансий", "карьер", "стажировк", "зарплат")
DRY_NEWS_MARKERS = ("новости", "news", "дайджест", "digest", "лента новостей",
                    "пресс-релиз", "обзор новостей")

CSV_COLUMNS = [
    "Название канала", "Ссылка на канал", "Подписчики", "Средние просмотры",
    "Вовлечённость %", "Контакт админа", "Ниша", "Описание канала",
]

USERNAME_PART = r"[A-Za-z][A-Za-z0-9_]{4,31}"
TME_RE = re.compile(rf"t\.me/(?:s/)?({USERNAME_PART})")
MENTION_RE = re.compile(rf"(?<![\w/@.])@({USERNAME_PART})\b")
COUNT_RE = re.compile(r"([\d\s.,]+)\s*(k|m|тыс\.?|млн\.?)?")

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


# ---------- Паузы ----------

def pause(bounds: tuple[float, float]) -> None:
    time.sleep(random.uniform(*bounds))


def log(message: str) -> None:
    print(f"{datetime.now():%H:%M:%S} {message}", flush=True)


# ---------- Разбор ----------

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


def has_marker(text: str, markers: tuple[str, ...]) -> bool:
    lowered = text.lower()
    return any(marker in lowered for marker in markers)


def detect_niches(text: str) -> list[str]:
    lowered = text.lower()
    return [niche for niche, markers in NICHE_MARKERS.items()
            if any(marker in lowered for marker in markers)]


def extract_usernames(text: str) -> set[str]:
    decoded = unquote(text)
    names: set[str] = set()

    for match in TME_RE.finditer(decoded):
        name = match.group(1)
        if name.lower() in RESERVED or name.isdigit():
            continue
        names.add(name)

    return names


def extract_contacts(description: str, own_username: str) -> list[str]:
    """@username админов и менеджеров из описания, кроме самого канала."""
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


# ---------- Память ----------

def username_from_link(link: str) -> str:
    match = TME_RE.search(link)
    return match.group(1).lower() if match else ""


def load_seen() -> set[str]:
    """Все уже проверенные каналы: из checked_usernames.txt и обоих CSV."""
    seen: set[str] = set()

    if CHECKED_FILE.is_file():
        for line in CHECKED_FILE.read_text(encoding="utf-8").splitlines():
            name = line.strip().lstrip("@")
            if name:
                seen.add(name.lower())

    for path in (OUTREACH_FILE, COMMENTS_FILE):
        if not path.is_file():
            continue
        with open(path, encoding="utf-8-sig", newline="") as file:
            for row in csv.DictReader(file):
                name = username_from_link(row.get("Ссылка на канал", ""))
                if name:
                    seen.add(name)

    return seen


def mark_checked(username: str) -> None:
    with open(CHECKED_FILE, "a", encoding="utf-8") as file:
        file.write(username.lower() + "\n")


def ensure_csv(path: Path) -> None:
    if path.is_file() and path.stat().st_size > 0:
        return
    with open(path, "w", encoding="utf-8-sig", newline="") as file:
        csv.DictWriter(file, fieldnames=CSV_COLUMNS).writeheader()


def append_row(path: Path, row: dict) -> None:
    """Дозапись одной строки. BOM сюда не попадает — он уже в заголовке."""
    ensure_csv(path)
    with open(path, "a", encoding="utf-8", newline="") as file:
        csv.DictWriter(file, fieldnames=CSV_COLUMNS).writerow(row)


def sort_csv(path: Path, key: str) -> int:
    if not path.is_file():
        return 0
    with open(path, encoding="utf-8-sig", newline="") as file:
        rows = list(csv.DictReader(file))
    if not rows:
        return 0

    rows.sort(key=lambda row: float(row[key]), reverse=True)
    with open(path, "w", encoding="utf-8-sig", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=CSV_COLUMNS)
        writer.writeheader()
        writer.writerows(rows)
    return len(rows)


# ---------- Поиск ----------

def search_query(ddgs: DDGS, query: str) -> set[str]:
    try:
        try:
            results = ddgs.text(query, region="ru-ru", max_results=RESULTS_PER_QUERY)
        except TypeError:
            results = ddgs.text(query, max_results=RESULTS_PER_QUERY)
        results = list(results or [])
    except Exception as error:
        log(f"    поиск не удался: {type(error).__name__}: {error}")
        raise

    found: set[str] = set()
    for result in results:
        blob = " ".join(str(result.get(key, "")) for key in ("href", "title", "body"))
        found |= extract_usernames(blob)
        found |= {name for name in MENTION_RE.findall(blob)
                  if name.lower() not in RESERVED}

    return found


# ---------- Проверка канала ----------

def fetch_channel(username: str) -> str | None:
    url = f"https://t.me/s/{username}"
    for attempt in range(1, MAX_RETRIES + 1):
        try:
            response = SESSION.get(url, timeout=TIMEOUT)
        except requests.RequestException as error:
            log(f"    сеть: {type(error).__name__}, повтор {attempt}/{MAX_RETRIES}")
            time.sleep(REQUEST_DELAY * attempt)
            continue

        if response.status_code == 200:
            return response.text

        retryable = response.status_code == 429 or response.status_code >= 500
        log(f"    HTTP {response.status_code}" + (", повтор" if retryable else ""))
        if not retryable:
            return None
        time.sleep(REQUEST_DELAY * attempt)

    return None


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
    html = fetch_channel(username)
    if html is None:
        return None

    soup = BeautifulSoup(html, "html.parser")

    # tgme_channel_info есть только у публичных каналов, у групп его нет.
    info = soup.select_one("div.tgme_channel_info")
    if info is None:
        return None

    title_el = info.select_one(".tgme_channel_info_header_title")
    title = title_el.get_text(" ", strip=True) if title_el else username

    subscribers = parse_subscribers(info)
    if subscribers is None:
        return None

    description_el = info.select_one(".tgme_channel_info_description")
    description = description_el.get_text(" ", strip=True) if description_el else ""
    description = " ".join(description.split())

    posts = parse_posts(soup)
    if not posts:
        return None

    last_post = posts[-1]["date"]
    if last_post < datetime.now(timezone.utc) - timedelta(days=MAX_POST_AGE_DAYS):
        return None

    views = [post["views"] for post in posts if post["views"] > 0][-POSTS_FOR_ENGAGEMENT:]
    average_views = sum(views) / len(views) if views else 0

    return {
        "username": username,
        "title": title,
        "description": description,
        "subscribers": subscribers,
        "average_views": int(round(average_views)),
        "engagement": round(average_views / subscribers * 100, 1) if subscribers else 0.0,
        "niches": detect_niches(f"{title} {description}"),
        "contacts": extract_contacts(description, username),
        "russian": is_russian(f"{title} {description}") and not has_marker(
            f"{title} {description}", KZ_MARKERS),
    }


def base_row(info: dict) -> dict:
    return {
        "Название канала": info["title"],
        "Ссылка на канал": f"https://t.me/{info['username']}",
        "Подписчики": info["subscribers"],
        "Средние просмотры": info["average_views"],
        "Вовлечённость %": info["engagement"],
        "Контакт админа": ", ".join(info["contacts"]),
        "Ниша": ", ".join(info["niches"]),
        "Описание канала": info["description"],
    }


def fits_outreach(info: dict) -> bool:
    text = f"{info['title']} {info['description']}"
    low, high = SUB_RANGE_OUTREACH
    return (
        info["russian"]
        and info["contacts"]
        and info["niches"]
        and low <= info["subscribers"] <= high
        and not has_marker(text, VACANCY_MARKERS)
        and not has_marker(info["title"], DRY_NEWS_MARKERS)
    )


def fits_comments(info: dict) -> bool:
    low, high = SUB_RANGE_COMMENTS
    return (
        info["russian"]
        and bool(TECH_NICHES & set(info["niches"]))
        and low <= info["subscribers"] <= high
    )


# ---------- Раунд ----------

def run_round(ddgs: DDGS, seen: set[str], round_no: int) -> tuple[int, int]:
    queries = SEARCH_QUERIES[:]
    random.shuffle(queries)

    found_outreach = 0
    found_comments = 0
    checked_now = 0
    errors_in_row = 0

    for query in queries:
        if checked_now >= ROUND_CHECK_LIMIT:
            break

        log(f"[Раунд {round_no}] запрос: {query}")
        try:
            candidates = search_query(ddgs, query)
            errors_in_row = 0
        except Exception:
            errors_in_row += 1
            if errors_in_row >= MAX_CONSECUTIVE_ERRORS:
                log(f"Поисковик ругается {errors_in_row} раз подряд, пауза {BLOCK_BREAK // 60} мин")
                time.sleep(BLOCK_BREAK)
                errors_in_row = 0
            continue

        fresh = [name for name in candidates if name.lower() not in seen]
        random.shuffle(fresh)
        log(f"    новых кандидатов: {len(fresh)}")

        for username in fresh:
            if checked_now >= ROUND_CHECK_LIMIT:
                break

            pause(CHECK_PAUSE)
            checked_now += 1
            seen.add(username.lower())
            mark_checked(username)

            info = inspect_channel(username)
            if info is None:
                continue

            if fits_outreach(info):
                append_row(OUTREACH_FILE, base_row(info))
                found_outreach += 1
            elif fits_comments(info):
                append_row(COMMENTS_FILE, base_row(info))
                found_comments += 1
            else:
                continue

            log(f"[Раунд {round_no}] Найдено для рассылки: {found_outreach} "
                f"| Для комментов: {found_comments}")

        pause(QUERY_PAUSE)

    return found_outreach, found_comments


def main() -> None:
    seen = load_seen()
    log(f"Уже проверено ранее: {len(seen)} каналов")

    round_no = 1
    total = {"outreach": 0, "comments": 0}

    def stop(signum, frame):  # noqa: ARG001 - обработчик сигнала
        raise KeyboardInterrupt

    signal.signal(signal.SIGTERM, stop)

    try:
        with DDGS() as ddgs:
            while True:
                log(f"=== Раунд {round_no} стартует, проверено всего: {len(seen)} ===")
                outreach, comments = run_round(ddgs, seen, round_no)
                total["outreach"] += outreach
                total["comments"] += comments

                log(f"[Раунд {round_no}] Найдено для рассылки: {outreach} "
                    f"| Для комментов: {comments}")
                log(f"Всего за ночь: рассылка {total['outreach']}, "
                    f"комменты {total['comments']}, проверено {len(seen)}")

                round_no += 1
                wait = random.uniform(*ROUND_BREAK)
                log(f"Перерыв {wait / 60:.1f} мин до следующего раунда")
                time.sleep(wait)
    except KeyboardInterrupt:
        log("Остановлено пользователем, сохраняю файлы")
    finally:
        outreach_saved = sort_csv(OUTREACH_FILE, "Вовлечённость %")
        comments_saved = sort_csv(COMMENTS_FILE, "Подписчики")
        log(f"Итог: channels_outreach.csv — {outreach_saved}, "
            f"channels_comments.csv — {comments_saved}, "
            f"проверено {len(seen)}")


if __name__ == "__main__":
    main()