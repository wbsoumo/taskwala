<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Services\TelegramMtproto\TelegramMessageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Python Telethon listener.
     */
    public function handle(Request $request, TelegramMessageProcessor $processor)
    {
        $expectedSecret = config('services.telethon.secret', env('TELEGRAM_MTPROTO_SECRET', 'taskwala_telethon_mtproto_sec_99182'));
        $receivedSecret = $request->header('X-Telegram-Secret');

        if ($receivedSecret !== $expectedSecret) {
            Log::warning('Unauthorized internal Telegram webhook call attempt', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unauthorized.',
            ], 401);
        }

        try {
            $payload = $request->all();
            $result = $processor->processPayload($payload);

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('Telegram webhook processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
