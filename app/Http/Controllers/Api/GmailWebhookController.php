<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGmailAlertJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GmailWebhookController extends Controller
{
    /**
     * Handle incoming Gmail alert webhook.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $signature = $request->header('X-Signature-SHA256');
        $secret = config('services.gmail_webhook.secret');

        if ($secret) {
            $computedSignature = hash_hmac('sha256', $request->getContent(), $secret);

            if (! $signature || ! hash_equals($computedSignature, strtolower($signature))) {
                Log::warning('Invalid Gmail webhook signature received', [
                    'received_signature' => $signature,
                    'client_ip' => $request->ip(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid webhook signature.',
                ], 401);
            }
        }

        $payload = $request->all();

        if (empty($payload['thread_id']) || empty($payload['message_id'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing thread_id or message_id in payload.',
            ], 422);
        }

        // Process job
        ProcessGmailAlertJob::dispatch($payload);

        return response()->json([
            'status' => 'success',
            'message' => 'Gmail webhook received and queued for processing.',
        ]);
    }
}
