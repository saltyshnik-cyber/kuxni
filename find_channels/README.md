# find_channels

Ночной сбор базы живых российских Telegram-каналов. Скрипт работает бесконечными раундами, пока вы не нажмёте `Ctrl+C`. Telegram API и `api_id` не нужны.

## Запуск

```bash
pip install -r requirements.txt
python find_channels.py --probe   # быстрая проверка, 10–30 секунд
python find_channels.py           # ночной режим до Ctrl+C