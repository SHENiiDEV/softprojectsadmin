<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    /**
     * Handle the incoming Telegram webhook request.
     */
    public function __invoke(Request $request, TelegramService $telegramService): JsonResponse
    {
        $update = $request->all();

        if (! empty($update)) {
            $telegramService->handleUpdate($update);
        }

        return response()->json(['status' => 'ok']);
    }
}
