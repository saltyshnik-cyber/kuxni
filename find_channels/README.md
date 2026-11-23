# find_channels

Скрипт собирает две базы русскоязычных Telegram-каналов за один запуск. Telegram API и `api_id` не нужны: кандидаты берутся из выдачи `ddgs`, качество проверяется по публичной веб-версии канала.

## Запуск

```bash
pip install -r requirements.txt
python find_channels.py