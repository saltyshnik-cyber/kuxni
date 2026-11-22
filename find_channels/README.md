# find_channels

Скрипт собирает базу живых русскоязычных Telegram-каналов (500–25 000 подписчиков) для партнёрства и розыгрышей. Telegram API и `api_id` не нужны: кандидаты берутся из поисковой выдачи и GitHub-подборок, качество проверяется по публичной веб-версии канала.

## Запуск

```bash
pip install -r requirements.txt
cp .env.example .env   # вписать BOT_TOKEN и ADMIN_CHAT_ID
python find_channels.py