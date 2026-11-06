import asyncio
import logging
import os
import re
import sys
from datetime import datetime
from pathlib import Path
from typing import Optional, List, Dict, Any

from telethon import TelegramClient, events
from telethon.sessions import StringSession
from telethon.errors import (
    SessionPasswordNeededError,
    PhoneCodeInvalidError,
    PhoneCodeExpiredError,
    PasswordHashInvalidError,
    FloodWaitError,
)
from telethon.tl.types import Channel, Chat, User, MessageEntityTextUrl, MessageEntityUrl

from config import API_ID, API_HASH, MEDIA_STORAGE_DIR

logger = logging.getLogger("telethon_manager")
logging.basicConfig(level=logging.WARNING, stream=sys.stderr, format="%(asctime)s [%(levelname)s] %(message)s")


class TelethonManager:
    def __init__(self):
        self.client: Optional[TelegramClient] = None
        self.api_id: int = API_ID
        self.api_hash: str = API_HASH
        self.current_phone: Optional[str] = None
        self.phone_code_hash: Optional[str] = None
        self.session_str: str = ""
        self.monitored_channel_ids: set = set()
        self.is_monitoring: bool = False
        self.callback_url: Optional[str] = None
        self.shared_secret: Optional[str] = None
        self._new_msg_handler = None
        self._edit_msg_handler = None
        self.sync_progress: Dict[str, Any] = {}

    def get_client(self, session_str: str = "", api_id: Optional[int] = None, api_hash: Optional[str] = None) -> TelegramClient:
        effective_api_id = api_id or self.api_id
        effective_api_hash = api_hash or self.api_hash

        if self.client and self.client.is_connected():
            return self.client

        from telethon.network import ConnectionTcpIntermediate
        session = StringSession(session_str or self.session_str)
        self.client = TelegramClient(
            session,
            effective_api_id,
            effective_api_hash,
            connection=ConnectionTcpIntermediate,
            connection_retries=15,
            timeout=30,
            retry_delay=2,
            auto_reconnect=True,
        )
        self.api_id = effective_api_id
        self.api_hash = effective_api_hash
        return self.client

    async def restore_session(self, session_str: str, api_id: Optional[int] = None, api_hash: Optional[str] = None) -> Dict[str, Any]:
        """Restore an existing string session."""
        try:
            self.session_str = session_str
            client = self.get_client(session_str, api_id, api_hash)
            if not client.is_connected():
                await client.connect()

            if await client.is_user_authorized():
                me = await client.get_me()
                return {
                    "success": True,
                    "connected": True,
                    "user": self._format_user(me),
                    "session_string": client.session.save(),
                }
            else:
                return {
                    "success": False,
                    "connected": False,
                    "message": "Session is expired or unauthorized.",
                }
        except Exception as e:
            logger.exception("Failed to restore session")
            return {"success": False, "connected": False, "error": str(e)}

    async def send_code(self, phone: str, api_id: Optional[int] = None, api_hash: Optional[str] = None) -> Dict[str, Any]:
        """Send login OTP to the specified phone number."""
        try:
            phone = phone.strip()
            self.current_phone = phone
            self.session_str = ""  # Start fresh session for new login
            client = self.get_client("", api_id, api_hash)

            if not client.is_connected():
                await client.connect()

            result = await client.send_code_request(phone)
            self.phone_code_hash = result.phone_code_hash

            return {
                "success": True,
                "phone": phone,
                "phone_code_hash": result.phone_code_hash,
                "message": f"Login code sent to {phone}. Please check your Telegram app or SMS.",
            }
        except FloodWaitError as e:
            return {"success": False, "error": f"Telegram flood wait: Please wait {e.seconds} seconds before trying again."}
        except Exception as e:
            logger.exception("Send code error")
            return {"success": False, "error": str(e)}

    async def verify_code(self, code: str, phone: Optional[str] = None, phone_code_hash: Optional[str] = None) -> Dict[str, Any]:
        """Verify the OTP code received by the user."""
        try:
            client = self.client
            if not client or not client.is_connected():
                return {"success": False, "error": "Client not connected. Please send code again."}

            target_phone = phone or self.current_phone
            target_hash = phone_code_hash or self.phone_code_hash

            try:
                user = await client.sign_in(phone=target_phone, code=code.strip(), phone_code_hash=target_hash)
                saved_session = client.session.save()
                self.session_str = saved_session

                return {
                    "success": True,
                    "needs_2fa": False,
                    "user": self._format_user(user),
                    "session_string": saved_session,
                    "message": "Successfully connected to Telegram!",
                }
            except SessionPasswordNeededError:
                return {
                    "success": True,
                    "needs_2fa": True,
                    "message": "Two-Step Verification (2FA) is enabled on this account. Please enter your password.",
                }
            except (PhoneCodeInvalidError, PhoneCodeExpiredError) as pe:
                return {"success": False, "error": str(pe)}
        except Exception as e:
            logger.exception("Verify code error")
            return {"success": False, "error": str(e)}

    async def verify_2fa(self, password: str) -> Dict[str, Any]:
        """Complete 2FA password verification."""
        try:
            client = self.client
            if not client or not client.is_connected():
                return {"success": False, "error": "Client not connected."}

            user = await client.sign_in(password=password)
            saved_session = client.session.save()
            self.session_str = saved_session

            return {
                "success": True,
                "needs_2fa": False,
                "user": self._format_user(user),
                "session_string": saved_session,
                "message": "Two-Step Verification successful! Connected.",
            }
        except PasswordHashInvalidError:
            return {"success": False, "error": "Invalid 2FA password. Please check and try again."}
        except Exception as e:
            logger.exception("Verify 2FA error")
            return {"success": False, "error": str(e)}

    async def get_me(self) -> Dict[str, Any]:
        """Get connected user details."""
        if not self.client or not self.client.is_connected():
            return {"connected": False}
        try:
            if await self.client.is_user_authorized():
                me = await self.client.get_me()
                return {"connected": True, "user": self._format_user(me)}
            return {"connected": False}
        except Exception as e:
            return {"connected": False, "error": str(e)}

    async def disconnect(self) -> Dict[str, Any]:
        """Disconnect and clear current session."""
        try:
            await self.stop_monitoring()
            if self.client and self.client.is_connected():
                await self.client.disconnect()
            self.client = None
            self.session_str = ""
            return {"success": True, "message": "Disconnected successfully."}
        except Exception as e:
            return {"success": False, "error": str(e)}

    async def get_dialogs(self) -> List[Dict[str, Any]]:
        """Fetch all channels and groups accessible to the account."""
        if not self.client or not self.client.is_connected():
            return []

        channels = []
        try:
            async for dialog in self.client.iter_dialogs():
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

        except Exception as e:
            logger.exception("Error fetching dialogs")

        return channels

    async def sync_history(
        self,
        channel_id: int,
        limit: int = 100,
        webhook_url: Optional[str] = None,
        secret: Optional[str] = None
    ) -> Dict[str, Any]:
        """Fetch historical messages from a channel and post them to Laravel."""
        if not self.client or not self.client.is_connected():
            return {"success": False, "error": "Client not connected."}

        target_url = webhook_url or self.callback_url
        target_secret = secret or self.shared_secret

        self.sync_progress[str(channel_id)] = {
            "status": "syncing",
            "imported": 0,
            "total_target": limit,
            "started_at": datetime.utcnow().isoformat(),
        }

        imported_count = 0
        deals_found = 0

        try:
            entity = await self.client.get_entity(channel_id)
            messages_batch = []

            async for message in self.client.iter_messages(entity, limit=limit):
                if not message:
                    continue

                payload = await self._serialize_message(message, channel_id)
                if payload:
                    messages_batch.append(payload)
                    imported_count += 1
                    if payload.get("extracted_urls"):
                        deals_found += 1

                # Send in batches of 15 or on loop
                if len(messages_batch) >= 15:
                    await self._post_webhook_batch(messages_batch, target_url, target_secret)
                    messages_batch = []

                self.sync_progress[str(channel_id)]["imported"] = imported_count

            if messages_batch:
                await self._post_webhook_batch(messages_batch, target_url, target_secret)

            self.sync_progress[str(channel_id)]["status"] = "synced"
            self.sync_progress[str(channel_id)]["completed_at"] = datetime.utcnow().isoformat()

            return {
                "success": True,
                "channel_id": channel_id,
                "messages_imported": imported_count,
                "deals_found": deals_found,
            }

        except Exception as e:
            logger.exception(f"Error syncing history for {channel_id}")
            self.sync_progress[str(channel_id)]["status"] = "error"
            self.sync_progress[str(channel_id)]["error"] = str(e)
            return {"success": False, "error": str(e)}

    async def start_monitoring(
        self,
        channel_ids: List[int],
        webhook_url: Optional[str] = None,
        secret: Optional[str] = None
    ) -> Dict[str, Any]:
        """Start continuous real-time monitoring of selected channels."""
        if not self.client or not self.client.is_connected():
            return {"success": False, "error": "Client not connected."}

        self.callback_url = webhook_url or self.callback_url
        self.shared_secret = secret or self.shared_secret
        self.monitored_channel_ids = set(channel_ids)

        if not self.monitored_channel_ids:
            await self.stop_monitoring()
            return {"success": True, "monitoring": False, "message": "No channels selected for monitoring."}

        # Remove existing handlers if already attached
        await self._detach_handlers()

        # Handler for new incoming messages
        @self.client.on(events.NewMessage(chats=list(self.monitored_channel_ids)))
        async def on_new_message(event):
            try:
                channel_id = event.chat_id
                payload = await self._serialize_message(event.message, channel_id, is_edit=False)
                if payload and self.callback_url:
                    await self._post_webhook_single(payload, self.callback_url, self.shared_secret)
            except Exception as e:
                logger.error(f"Error in on_new_message: {e}")

        # Handler for edited messages
        @self.client.on(events.MessageEdited(chats=list(self.monitored_channel_ids)))
        async def on_message_edited(event):
            try:
                channel_id = event.chat_id
                payload = await self._serialize_message(event.message, channel_id, is_edit=True)
                if payload and self.callback_url:
                    await self._post_webhook_single(payload, self.callback_url, self.shared_secret)
            except Exception as e:
                logger.error(f"Error in on_message_edited: {e}")

        self._new_msg_handler = on_new_message
        self._edit_msg_handler = on_message_edited
        self.is_monitoring = True

        logger.info(f"Started monitoring {len(self.monitored_channel_ids)} channels")
        return {
            "success": True,
            "monitoring": True,
            "channels_count": len(self.monitored_channel_ids),
            "message": f"Live monitoring started for {len(self.monitored_channel_ids)} channels.",
        }

    async def stop_monitoring(self) -> Dict[str, Any]:
        """Stop real-time monitoring."""
        await self._detach_handlers()
        self.is_monitoring = False
        return {"success": True, "monitoring": False, "message": "Monitoring stopped."}

    async def _detach_handlers(self):
        if self.client:
            if self._new_msg_handler:
                self.client.remove_event_handler(self._new_msg_handler)
                self._new_msg_handler = None
            if self._edit_msg_handler:
                self.client.remove_event_handler(self._edit_msg_handler)
                self._edit_msg_handler = None

    async def _serialize_message(self, message, channel_id: int, is_edit: bool = False) -> Optional[Dict[str, Any]]:
        """Extract text, entities, URLs and download photo media."""
        text = message.text or message.message or ""
        entities = message.entities or []

        # Extract URLs from text and Telegram message entities
        urls = self._extract_urls(text, entities)

        # Download photo or thumbnail if present
        media_type = "none"
        media_path = None

        try:
            if message.photo:
                media_type = "photo"
                filename = f"tg_{abs(channel_id)}_{message.id}.jpg"
                dest_path = MEDIA_STORAGE_DIR / filename
                if not dest_path.exists():
                    await message.download_media(file=str(dest_path))
                media_path = f"telegram_media/{filename}"
            elif message.video:
                media_type = "video"
            elif message.document:
                media_type = "document"
            elif message.web_preview:
                media_type = "web_page"
        except Exception as me:
            logger.warning(f"Failed to download media for msg {message.id}: {me}")

        posted_at = message.date.isoformat() if message.date else datetime.utcnow().isoformat()
        edited_at = message.edit_date.isoformat() if getattr(message, "edit_date", None) else None

        return {
            "channel_id": channel_id,
            "message_id": message.id,
            "sender_id": message.sender_id,
            "text": text,
            "media_type": media_type,
            "media_local_path": media_path,
            "extracted_urls": urls,
            "posted_at": posted_at,
            "is_edited": is_edit or bool(edited_at),
            "edited_at": edited_at,
            "raw": {
                "id": message.id,
                "views": getattr(message, "views", 0),
                "forwards": getattr(message, "forwards", 0),
            }
        }

    def _extract_urls(self, text: str, entities: list) -> List[str]:
        urls = []
        # From entities
        for ent in entities:
            if isinstance(ent, MessageEntityTextUrl) and ent.url:
                urls.append(ent.url)
            elif isinstance(ent, MessageEntityUrl):
                offset = ent.offset
                length = ent.length
                url_str = text[offset:offset + length]
                urls.append(url_str)

        # From regex
        regex_matches = re.findall(r"https?://[^\s<>\"']+", text)
        for u in regex_matches:
            cleaned = u.rstrip(".,;:!?)]")
            if cleaned not in urls:
                urls.append(cleaned)

        # Filter out t.me internal links
        valid_urls = []
        for u in urls:
            if "t.me" not in u and "telegram.org" not in u and "telegram.me" not in u:
                valid_urls.append(u)

        return valid_urls

    async def _post_webhook_batch(self, messages: List[Dict[str, Any]], url: str, secret: str):
        if not url or not messages:
            return
        import aiohttp
        try:
            async with aiohttp.ClientSession() as session:
                headers = {
                    "Content-Type": "application/json",
                    "X-Telegram-Secret": secret or "",
                }
                payload = {"batch": True, "messages": messages}
                async with session.post(url, json=payload, headers=headers, timeout=10) as resp:
                    if resp.status != 200:
                        logger.warning(f"Webhook batch returned HTTP {resp.status}")
        except Exception as e:
            logger.error(f"Failed to post webhook batch to {url}: {e}")

    async def _post_webhook_single(self, message: Dict[str, Any], url: str, secret: str):
        if not url or not message:
            return
        import aiohttp
        try:
            async with aiohttp.ClientSession() as session:
                headers = {
                    "Content-Type": "application/json",
                    "X-Telegram-Secret": secret or "",
                }
                async with session.post(url, json=message, headers=headers, timeout=8) as resp:
                    if resp.status != 200:
                        logger.warning(f"Webhook single returned HTTP {resp.status}")
        except Exception as e:
            logger.error(f"Failed to post webhook single to {url}: {e}")

    def _format_user(self, user) -> Dict[str, Any]:
        return {
            "id": getattr(user, "id", None),
            "first_name": getattr(user, "first_name", ""),
            "last_name": getattr(user, "last_name", ""),
            "username": getattr(user, "username", None),
            "phone": getattr(user, "phone", None),
        }
