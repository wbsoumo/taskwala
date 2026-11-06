#!/usr/bin/env python3
"""
Taskwala Telethon CLI Interface for cPanel / Direct Execution.
Allows executing Telegram MTProto commands directly without requiring a background daemon on port 5001.
"""

import argparse
import asyncio
import json
import logging
import os
import sys
from pathlib import Path

# Silence all Telethon and internal loggers on stdout
logging.basicConfig(level=logging.ERROR, stream=sys.stderr)
for _log in ["telethon", "telethon.network", "telethon.client", "telethon_manager"]:
    logging.getLogger(_log).setLevel(logging.ERROR)

# Add python_service directory to sys.path
SCRIPT_DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(SCRIPT_DIR))

try:
    from telethon import TelegramClient
    from telethon.sessions import StringSession
    from telethon.errors import (
        SessionPasswordNeededError,
        PhoneCodeInvalidError,
        PhoneCodeExpiredError,
        PasswordHashInvalidError,
        FloodWaitError,
    )
    from telethon.tl.types import Channel, Chat, User
    from config import API_ID, API_HASH, MEDIA_STORAGE_DIR
    from telethon_manager import TelethonManager
except ImportError as ie:
    # Output json error and exit cleanly
    print(json.dumps({
        "success": False,
        "error": f"Missing Python dependency: {ie}. Please install requirements: pip install telethon aiohttp python-dotenv",
        "missing_module": str(ie)
    }))
    sys.exit(1)


def get_client(session_str: str, api_id: int, api_hash: str) -> TelegramClient:
    from telethon.network import ConnectionTcpIntermediate
    return TelegramClient(
        StringSession(session_str or ""),
        api_id,
        api_hash,
        connection=ConnectionTcpIntermediate,
        connection_retries=15,
        timeout=30,
        retry_delay=2,
        auto_reconnect=True,
    )


async def cmd_send_code(args):
    api_id = int(args.api_id or API_ID)
    api_hash = args.api_hash or API_HASH
    phone = args.phone.strip()

    client = get_client("", api_id, api_hash)
    try:
        await client.connect()
        result = await client.send_code_request(phone)
        session_str = client.session.save()

        print(json.dumps({
            "success": True,
            "phone": phone,
            "phone_code_hash": result.phone_code_hash,
            "session_string": session_str,
            "message": f"Login code sent to {phone}. Please check your Telegram app or SMS.",
        }))
    except FloodWaitError as e:
        print(json.dumps({
            "success": False,
            "error": f"Telegram FloodWait: Please wait {e.seconds} seconds before trying again.",
            "flood_wait_seconds": e.seconds,
        }))
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": str(e),
        }))
    finally:
        await client.disconnect()


async def cmd_verify_code(args):
    api_id = int(args.api_id or API_ID)
    api_hash = args.api_hash or API_HASH
    phone = args.phone.strip()
    code = args.code.strip()
    phone_code_hash = args.hash or args.phone_code_hash
    session_str = args.session or ""

    manager = TelethonManager()
    client = get_client(session_str, api_id, api_hash)

    try:
        await client.connect()
        try:
            user = await client.sign_in(phone=phone, code=code, phone_code_hash=phone_code_hash)
            saved_session = client.session.save()
            print(json.dumps({
                "success": True,
                "needs_2fa": False,
                "session_string": saved_session,
                "user": manager._format_user(user),
                "message": "Successfully connected to Telegram!",
            }))
        except SessionPasswordNeededError:
            saved_session = client.session.save()
            print(json.dumps({
                "success": True,
                "needs_2fa": True,
                "session_string": saved_session,
                "message": "Two-Step Verification (2FA) is enabled on this account. Please enter your 2FA password.",
            }))
        except (PhoneCodeInvalidError, PhoneCodeExpiredError) as pe:
            print(json.dumps({
                "success": False,
                "error": str(pe),
            }))
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": str(e),
        }))
    finally:
        await client.disconnect()


async def cmd_verify_2fa(args):
    api_id = int(args.api_id or API_ID)
    api_hash = args.api_hash or API_HASH
    password = args.password
    session_str = args.session or ""

    if not session_str:
        print(json.dumps({
            "success": False,
            "error": "No session provided for 2FA verification.",
        }))
        return

    manager = TelethonManager()
    client = get_client(session_str, api_id, api_hash)

    try:
        await client.connect()
        try:
            user = await client.sign_in(password=password)
            saved_session = client.session.save()
            print(json.dumps({
                "success": True,
                "needs_2fa": False,
                "session_string": saved_session,
                "user": manager._format_user(user),
                "message": "Two-Step Verification successful! Connected.",
            }))
        except PasswordHashInvalidError:
            print(json.dumps({
                "success": False,
                "error": "Invalid 2FA password. Please check and try again.",
            }))
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": str(e),
        }))
    finally:
        await client.disconnect()


async def cmd_fetch_channels(args):
    api_id = int(args.api_id or API_ID)
    api_hash = args.api_hash or API_HASH
    session_str = args.session or ""

    if not session_str:
        print(json.dumps({
            "success": False,
            "error": "Session string is required to fetch channels.",
        }))
        return

    client = get_client(session_str, api_id, api_hash)

    try:
        await client.connect()
        if not await client.is_user_authorized():
            print(json.dumps({
                "success": False,
                "error": "Session is expired or unauthorized.",
            }))
            return

        channels = []
        async for dialog in client.iter_dialogs():
            entity = dialog.entity
            if isinstance(entity, Channel):
                channel_type = "supergroup" if getattr(entity, "megagroup", False) else "channel"
                channels.append({
                    "id": dialog.id,
                    "title": dialog.name,
                    "username": getattr(entity, "username", None),
                    "type": channel_type,
                    "participants_count": getattr(entity, "participants_count", None),
                    "access_hash": str(getattr(entity, "access_hash", "")),
                    "is_broadcast": getattr(entity, "broadcast", False),
                })
            elif isinstance(entity, Chat):
                channels.append({
                    "id": dialog.id,
                    "title": dialog.name,
                    "username": None,
                    "type": "group",
                    "participants_count": getattr(entity, "participants_count", None),
                    "access_hash": "",
                    "is_broadcast": False,
                })

        print(json.dumps({
            "success": True,
            "channels": channels,
            "count": len(channels),
        }))
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": str(e),
        }))
    finally:
        await client.disconnect()


async def cmd_sync_history(args):
    api_id = int(args.api_id or API_ID)
    api_hash = args.api_hash or API_HASH
    session_str = args.session or ""
    channel_id = int(args.channel_id)
    limit = int(args.limit or 50)
    webhook_url = args.webhook_url
    secret = args.secret

    if not session_str:
        print(json.dumps({
            "success": False,
            "error": "Session string is required.",
        }))
        return

    manager = TelethonManager()
    manager.api_id = api_id
    manager.api_hash = api_hash
    manager.session_str = session_str

    client = get_client(session_str, api_id, api_hash)
    manager.client = client

    try:
        await client.connect()
        if not await client.is_user_authorized():
            print(json.dumps({
                "success": False,
                "error": "Session is expired or unauthorized.",
            }))
            return

        entity = await client.get_entity(channel_id)
        messages_batch = []
        deals_found = 0

        async for message in client.iter_messages(entity, limit=limit):
            if not message:
                continue

            payload = await manager._serialize_message(message, channel_id)
            if payload:
                messages_batch.append(payload)
                if payload.get("extracted_urls"):
                    deals_found += 1

        # Post to webhook if specified
        if webhook_url and messages_batch:
            await manager._post_webhook_batch(messages_batch, webhook_url, secret)

        print(json.dumps({
            "success": True,
            "channel_id": channel_id,
            "messages_imported": len(messages_batch),
            "deals_found": deals_found,
            "messages": messages_batch,
        }))
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": str(e),
        }))
    finally:
        await client.disconnect()


async def cmd_check_status(args):
    import telethon
    api_id = int(args.api_id or API_ID)
    api_hash = args.api_hash or API_HASH
    session_str = args.session or ""

    result = {
        "success": True,
        "mode": "cpanel_cli",
        "telethon_installed": True,
        "telethon_version": telethon.__version__,
        "connected": False,
    }

    if session_str:
        client = get_client(session_str, api_id, api_hash)
        try:
            await client.connect()
            if await client.is_user_authorized():
                me = await client.get_me()
                manager = TelethonManager()
                result["connected"] = True
                result["user"] = manager._format_user(me)
            await client.disconnect()
        except Exception as e:
            result["session_error"] = str(e)

    print(json.dumps(result))


class JsonArgumentParser(argparse.ArgumentParser):
    def error(self, message):
        print(json.dumps({"success": False, "error": f"CLI argument error: {message}"}))
        sys.exit(2)


def main():
    parser = JsonArgumentParser(description="Taskwala Telethon CLI for cPanel")
    subparsers = parser.add_subparsers(dest="action", required=True)

    # send-code
    p_send = subparsers.add_parser("send-code")
    p_send.add_argument("--phone", required=True)
    p_send.add_argument("--api-id", default=None)
    p_send.add_argument("--api-hash", default=None)

    # verify-code
    p_verify = subparsers.add_parser("verify-code")
    p_verify.add_argument("--phone", required=True)
    p_verify.add_argument("--code", required=True)
    p_verify.add_argument("--hash", "--phone-code-hash", dest="hash", required=False, default="")
    p_verify.add_argument("--session", default="")
    p_verify.add_argument("--api-id", default=None)
    p_verify.add_argument("--api-hash", default=None)

    # verify-2fa
    p_2fa = subparsers.add_parser("verify-2fa")
    p_2fa.add_argument("--password", required=True)
    p_2fa.add_argument("--session", required=True)
    p_2fa.add_argument("--api-id", default=None)
    p_2fa.add_argument("--api-hash", default=None)

    # fetch-channels
    p_chan = subparsers.add_parser("fetch-channels")
    p_chan.add_argument("--session", required=True)
    p_chan.add_argument("--api-id", default=None)
    p_chan.add_argument("--api-hash", default=None)

    # sync-history
    p_sync = subparsers.add_parser("sync-history")
    p_sync.add_argument("--session", required=True)
    p_sync.add_argument("--channel-id", required=True)
    p_sync.add_argument("--limit", type=int, default=50)
    p_sync.add_argument("--webhook-url", default=None)
    p_sync.add_argument("--secret", default=None)
    p_sync.add_argument("--api-id", default=None)
    p_sync.add_argument("--api-hash", default=None)

    # check-status
    p_check = subparsers.add_parser("check-status")
    p_check.add_argument("--session", default="")
    p_check.add_argument("--api-id", default=None)
    p_check.add_argument("--api-hash", default=None)

    args = parser.parse_args()

    action_map = {
        "send-code": cmd_send_code,
        "verify-code": cmd_verify_code,
        "verify-2fa": cmd_verify_2fa,
        "fetch-channels": cmd_fetch_channels,
        "sync-history": cmd_sync_history,
        "check-status": cmd_check_status,
    }

    func = action_map.get(args.action)
    if func:
        asyncio.run(func(args))
    else:
        print(json.dumps({"success": False, "error": f"Unknown action: {args.action}"}))
        sys.exit(1)


if __name__ == "__main__":
    main()
