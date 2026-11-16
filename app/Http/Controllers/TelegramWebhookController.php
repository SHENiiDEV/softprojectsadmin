<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Handle the incoming Telegram webhook request.
     */
    public function __invoke(Request $request, TelegramService $telegramService): JsonResponse
    {
        $secret = config('services.telegram.webhook_secret');

        if (! $secret) {
            Log::error('Telegram webhook rejected: TELEGRAM_WEBHOOK_SECRET is not configured.');

            return response()->json(['status' => 'error', 'message' => 'Webhook is not configured.'], 503);
        }

        $received = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if (! hash_equals((string) $secret, $received)) {
            Log::warning('Invalid Telegram webhook secret token received', ['client_ip' => $request->ip()]);

            return response()->json(['status' => 'error', 'message' => 'Invalid secret token.'], 403);
        }

        $update = $request->all();

        if (! empty($update)) {
            $telegramService->handleUpdate($update);
        }

        return response()->json(['status' => 'ok']);
    }
}
