# find_channels

Ночной сбор базы живых российских Telegram-каналов. Скрипт работает бесконечными раундами, пока вы не нажмёте `Ctrl+C`. Telegram API и `api_id` не нужны: кандидаты берутся из выдачи `ddgs`, качество проверяется по публичной веб-версии канала.

## Запуск

```bash
pip install -r requirements.txt
python find_channels.py