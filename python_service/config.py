import os
from pathlib import Path
from dotenv import load_dotenv

# Base directory of the Laravel application
BASE_DIR = Path(__file__).resolve().parent.parent

# Load .env from Laravel root if present
load_dotenv(BASE_DIR / ".env")

API_ID = int(os.getenv("TELEGRAM_API_ID", "2040"))
API_HASH = os.getenv("TELEGRAM_API_HASH", "b18441a1ff607e10a989891a5462e627")
SERVICE_HOST = os.getenv("TELETHON_SERVICE_HOST", "127.0.0.1")
SERVICE_PORT = int(os.getenv("TELETHON_SERVICE_PORT", "5001"))
LARAVEL_URL = os.getenv("APP_URL", "http://127.0.0.1:8000").rstrip("/")
SHARED_SECRET = os.getenv("TELEGRAM_MTPROTO_SECRET", "taskwala_telethon_mtproto_sec_99182")
MEDIA_STORAGE_DIR = BASE_DIR / "storage" / "app" / "public" / "telegram_media"

os.makedirs(MEDIA_STORAGE_DIR, exist_ok=True)
