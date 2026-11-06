import asyncio
import json
import logging
from aiohttp import web

from config import SERVICE_HOST, SERVICE_PORT, SHARED_SECRET, LARAVEL_URL
from telethon_manager import TelethonManager

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
logger = logging.getLogger("telethon_server")

manager = TelethonManager()


# Authentication middleware for internal requests
@web.middleware
async def auth_middleware(request, handler):
    # Allow local health check
    if request.path == "/health" and request.method == "GET":
        return await handler(request)

    secret = request.headers.get("X-Service-Secret") or request.headers.get("Authorization")
    if secret:
        secret = secret.replace("Bearer ", "").strip()

    if secret != SHARED_SECRET:
        return web.json_response({"success": False, "error": "Unauthorized request."}, status=401)

    return await handler(request)


# ------------------- API HANDLERS ------------------- #

async def handle_health(request):
    """Health and status endpoint."""
    user_info = await manager.get_me()
    return web.json_response({
        "status": "healthy",
        "service": "Taskwala Telethon MTProto Service",
        "connected": user_info.get("connected", False),
        "user": user_info.get("user"),
        "monitoring": manager.is_monitoring,
        "monitored_channels": list(manager.monitored_channel_ids),
        "sync_progress": manager.sync_progress,
    })


async def handle_send_code(request):
    data = await request.json()
    phone = data.get("phone", "")
    api_id = data.get("api_id")
    api_hash = data.get("api_hash")

    if not phone:
        return web.json_response({"success": False, "error": "Phone number is required."}, status=400)

    result = await manager.send_code(phone, api_id, api_hash)
    status_code = 200 if result.get("success") else 400
    return web.json_response(result, status=status_code)


async def handle_verify_code(request):
    data = await request.json()
    code = data.get("code", "")
    phone = data.get("phone")
    phone_code_hash = data.get("phone_code_hash")

    if not code:
        return web.json_response({"success": False, "error": "Verification code is required."}, status=400)

    result = await manager.verify_code(code, phone, phone_code_hash)
    status_code = 200 if result.get("success") else 400
    return web.json_response(result, status=status_code)


async def handle_verify_2fa(request):
    data = await request.json()
    password = data.get("password", "")

    if not password:
        return web.json_response({"success": False, "error": "2FA password is required."}, status=400)

    result = await manager.verify_2fa(password)
    status_code = 200 if result.get("success") else 400
    return web.json_response(result, status=status_code)


async def handle_restore_session(request):
    data = await request.json()
    session_string = data.get("session_string", "")
    api_id = data.get("api_id")
    api_hash = data.get("api_hash")

    if not session_string:
        return web.json_response({"success": False, "error": "Session string is required."}, status=400)

    result = await manager.restore_session(session_string, api_id, api_hash)
    return web.json_response(result)


async def handle_disconnect(request):
    result = await manager.disconnect()
    return web.json_response(result)


async def handle_get_channels(request):
    channels = await manager.get_dialogs()
    return web.json_response({
        "success": True,
        "count": len(channels),
        "channels": channels,
    })


async def handle_sync_history(request):
    data = await request.json()
    channel_id = data.get("channel_id")
    limit = int(data.get("limit", 100))
    webhook_url = data.get("webhook_url") or f"{LARAVEL_URL}/api/internal/telegram-webhook"
    secret = data.get("secret") or SHARED_SECRET

    if not channel_id:
        return web.json_response({"success": False, "error": "channel_id is required."}, status=400)

    # Launch sync as background asyncio task to prevent HTTP timeout
    asyncio.create_task(manager.sync_history(int(channel_id), limit, webhook_url, secret))

    return web.json_response({
        "success": True,
        "message": f"Historical synchronization started for channel {channel_id} (limit: {limit}).",
        "channel_id": channel_id,
        "status": "syncing",
    })


async def handle_start_monitoring(request):
    data = await request.json()
    channel_ids = data.get("channel_ids", [])
    webhook_url = data.get("webhook_url") or f"{LARAVEL_URL}/api/internal/telegram-webhook"
    secret = data.get("secret") or SHARED_SECRET

    int_channel_ids = [int(cid) for cid in channel_ids if str(cid).lstrip("-").isdigit()]
    result = await manager.start_monitoring(int_channel_ids, webhook_url, secret)
    return web.json_response(result)


async def handle_stop_monitoring(request):
    result = await manager.stop_monitoring()
    return web.json_response(result)


async def handle_monitoring_status(request):
    return web.json_response({
        "monitoring": manager.is_monitoring,
        "monitored_channels": list(manager.monitored_channel_ids),
        "sync_progress": manager.sync_progress,
    })


# ------------------- APP INITIALIZATION ------------------- #

def create_app():
    app = web.Application(middlewares=[auth_middleware])
    app.router.add_get("/health", handle_health)
    app.router.add_post("/auth/send-code", handle_send_code)
    app.router.add_post("/auth/verify-code", handle_verify_code)
    app.router.add_post("/auth/verify-2fa", handle_verify_2fa)
    app.router.add_post("/auth/restore-session", handle_restore_session)
    app.router.add_post("/auth/disconnect", handle_disconnect)

    app.router.add_get("/channels", handle_get_channels)
    app.router.add_post("/channels/sync-history", handle_sync_history)

    app.router.add_post("/monitoring/start", handle_start_monitoring)
    app.router.add_post("/monitoring/stop", handle_stop_monitoring)
    app.router.add_get("/monitoring/status", handle_monitoring_status)

    return app


if __name__ == "__main__":
    logger.info(f"Starting Taskwala Telethon MTProto Server on {SERVICE_HOST}:{SERVICE_PORT}...")
    app = create_app()
    web.run_app(app, host=SERVICE_HOST, port=SERVICE_PORT)
