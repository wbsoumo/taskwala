#!/usr/bin/env bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "=== Setting up Taskwala Telegram MTProto Telethon Environment ==="

if [ ! -d "venv" ]; then
    echo "Creating virtualenv..."
    python3 -m venv venv
fi

echo "Installing requirements..."
./venv/bin/pip install --upgrade pip
./venv/bin/pip install -r requirements.txt

echo "Testing modules..."
./venv/bin/python3 -c "import telethon, aiohttp; print('Telethon MTProto ready! Telethon:', telethon.__version__)"

echo "=== Setup complete! ==="
echo "To run the service in foreground: ./venv/bin/python3 server.py"
