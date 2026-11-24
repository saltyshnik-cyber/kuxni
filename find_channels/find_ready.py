"""Сбор 50–100 живых русскоязычных каналов с 400–2 000 подписчиков.

Кандидаты берутся из поиска ddgs (lite → html), каждый проверяется по
публичной странице https://t.me/s/<username>. Результат: channels_ready.txt,
channels_ready.csv и channels_ready.html (с кликабельными ссылками).
Повторный запуск продолжает с того места, где остановились.
"""
from __future__ import annotations

import argparse
import csv
import html
import random
import time
from pathlib import Path

from find_channels import (
    CHECK_PAUSE, DDGS, QUERY_PAUSE, has_marker, inspect_channel, log, pause, search_query,
)

BASE_DIR = Path(__file__).resolve().parent
TXT_FILE = BASE_DIR / "channels_ready.txt"
CSV_FILE = BASE_DIR / "channels_ready.csv"
HTML_FILE = BASE_DIR / "channels_ready.html"
CHECKED_FILE = BASE_DIR / "checked_ready.txt"

SUBSCRIBERS_RANGE = (400, 2_000)
MIN_VIEWS = 250
MIN_TARGET = 50
MAX_TARGET = 100
DESCRIPTION_LIMIT = 120

STOP_WORDS = ("профбюро", "министерство", "администрация", "магазин одежды")

QUERIES = [
    'site:t.me "по рекламе" "розыгрыш"',
    'site:t.me "по рекламе" "нейросети"',
    'site:t.me "по рекламе" "блог"',
    'site:t.me "по рекламе" "крипта"',
    'site:t.me "по рекламе" "разработка"',
    'site:t.me "сотрудничество" "подписчиков"',
    'site:t.me "сотрудничество" "нейросети"',
    'site:t.me "сотрудничество" "блог"',
    'site:t.me "реклама" "розыгрыш"',
    'site:t.me "по вопросам рекламы" "нейросети"',
    'site:t.me/s "по рекламе" "ии"',
    'site:t.me/s "по рекламе" "программирование"',
    'site:tgstat.ru/channel "розыгрыш" "блоги"',
    'site:tgstat.ru/channel "розыгрыш" "технологии"',
    'site:tgstat.ru/channel "нейросети" "реклама"',
    'site:tgstat.ru/channel "блог" "реклама"',
    'site:tgstat.ru/channel "крипта" "реклама"',
    'site:telemetr.io "нейросети" "реклама"',
    'site:telemetr.io "блог" "розыгрыш"',
]

CSV_COLUMNS = ["Канал", "Подписчики", "Просмотры", "Контакт админа", "Описание", "Ссылка"]


def short(text: str) -> str:
    cleaned = " ".join(text.replace("|", "/").split())
    if len(cleaned) <= DESCRIPTION_LIMIT:
        return cleaned
    return cleaned[:DESCRIPTION_LIMIT - 1].rstrip() + "…"


def fits(info: dict) -> bool:
    low, high = SUBSCRIBERS_RANGE
    text = f"{info['title']} {info['description']}"
    return (
        info["russian"]
        and bool(info["contacts"])
        and low <= info["subscribers"] <= high
        and info["average_views"] >= MIN_VIEWS
        and not has_marker(text, STOP_WORDS)
    )


def to_row(info: dict) -> dict:
    return {
        "Канал": f"@{info['username']}",
        "Подписчики": info["subscribers"],
        "Просмотры": info["average_views"],
        "Контакт админа": info["contacts"][0],
        "Описание": short(info["description"]),
        "Ссылка": f"https://t.me/{info['username']}",
    }


# ---------- Файлы ----------

def load_rows() -> list[dict]:
    if not CSV_FILE.is_file():
        return []
    with open(CSV_FILE, encoding="utf-8-sig", newline="") as file:
        return list(csv.DictReader(file))


def load_seen(rows: list[dict]) -> set[str]:
    seen = {row["Канал"].lstrip("@").lower() for row in rows}
    if CHECKED_FILE.is_file():
        seen |= {line.strip().lower() for line in
                 CHECKED_FILE.read_text(encoding="utf-8").splitlines() if line.strip()}
    return seen


def remember(username: str) -> None:
    with open(CHECKED_FILE, "a", encoding="utf-8") as file:
        file.write(username.lower() + "\n")


def txt_line(row: dict) -> str:
    return (f"{row['Канал']} | {row['Подписчики']} | {row['Просмотры']} | "
            f"{row['Контакт админа']} | {row['Описание']} | {row['Ссылка']}")


def html_row(row: dict) -> str:
    admin = row["Контакт админа"]
    return (
        "<tr>"
        f"<td><a href=\"{html.escape(row['Ссылка'])}\" target=\"_blank\" rel=\"noopener\">"
        f"{html.escape(row['Канал'])}</a></td>"
        f"<td>{row['Подписчики']}</td><td>{row['Просмотры']}</td>"
        f"<td><a href=\"https://t.me/{html.escape(admin.lstrip('@'))}\" target=\"_blank\" "
        f"rel=\"noopener\">{html.escape(admin)}</a></td>"
        f"<td>{html.escape(row['Описание'])}</td>"
        "</tr>"
    )


def save_all(rows: list[dict]) -> None:
    with open(CSV_FILE, "w", encoding="utf-8-sig", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=CSV_COLUMNS)
        writer.writeheader()
        writer.writerows(rows)

    TXT_FILE.write_text("\n".join(txt_line(row) for row in rows) + "\n", encoding="utf-8")

    body = "\n".join(html_row(row) for row in rows)
    HTML_FILE.write_text(
        "<!doctype html><html lang=\"ru\"><head><meta charset=\"utf-8\">"
        "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
        "<title>Готовые каналы</title>"
        "<style>body{font:15px system-ui;margin:24px}table{border-collapse:collapse;width:100%}"
        "td,th{border:1px solid #ccc;padding:8px;text-align:left;vertical-align:top}"
        "th{background:#f2f2f2}</style></head><body>"
        f"<h1>Готовые каналы: {len(rows)}</h1><table>"
        "<tr><th>Канал</th><th>Подписчики</th><th>Просмотры</th><th>Админ</th><th>Описание</th></tr>"
        f"{body}</table></body></html>",
        encoding="utf-8",
    )


# ---------- Сбор ----------

def check_candidates(names: list[str], rows: list[dict], seen: set[str], target: int) -> None:
    for username in names:
        if len(rows) >= target:
            return

        pause(CHECK_PAUSE)
        seen.add(username.lower())
        remember(username)

        info = inspect_channel(username)
        if info is None or not fits(info):
            continue

        rows.append(to_row(info))
        save_all(rows)
        log(f"[{len(rows)}/{target}] {txt_line(rows[-1])}")


def collect(target: int) -> list[dict]:
    rows = load_rows()
    seen = load_seen(rows)
    log(f"Уже в базе: {len(rows)}, проверено ранее: {len(seen)}, цель: {target}")

    queries = QUERIES[:]
    random.shuffle(queries)

    with DDGS() as ddgs:
        for query in queries:
            if len(rows) >= target:
                break

            log(f"запрос: {query}")
            try:
                found, _ = search_query(ddgs, query)
            except Exception as error:
                log(f"    поиск упал: {type(error).__name__}: {error}")
                continue

            fresh = [name for name in found if name.lower() not in seen]
            random.shuffle(fresh)
            log(f"    новых кандидатов: {len(fresh)}")
            check_candidates(fresh, rows, seen, target)
            pause(QUERY_PAUSE)

    return rows


def print_report(rows: list[dict]) -> None:
    print("\n=== Готовый список ===", flush=True)
    for row in rows:
        print(txt_line(row), flush=True)

    print(f"\nИтого: {len(rows)}", flush=True)
    if len(rows) < MIN_TARGET:
        print(f"Набрано меньше {MIN_TARGET}: запустите скрипт ещё раз, "
              "прогресс сохранён и проверенные каналы не повторятся.", flush=True)
    print(f"Файлы: {TXT_FILE.name}, {CSV_FILE.name}, {HTML_FILE.name} "
          "(в html все ссылки кликабельны)", flush=True)


def main() -> None:
    parser = argparse.ArgumentParser(description="Каналы 400–2 000 подписчиков с контактом админа")
    parser.add_argument("--target", type=int, default=MAX_TARGET,
                        help=f"сколько каналов собрать (по умолчанию {MAX_TARGET})")
    args = parser.parse_args()

    started = time.time()
    try:
        rows = collect(max(1, args.target))
    except KeyboardInterrupt:
        log("Остановлено пользователем")
        rows = load_rows()

    log(f"Готово за {(time.time() - started) / 60:.1f} мин")
    print_report(rows)


if __name__ == "__main__":
    main()
