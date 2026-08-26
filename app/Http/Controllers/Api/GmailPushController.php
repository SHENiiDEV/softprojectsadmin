<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGmailSyncJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GmailPushController extends Controller
{
    /**
     * Handle incoming Google Cloud Pub/Sub push notification for Gmail updates.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $messageData = $request->input('message.data');

        if (! $messageData) {
            return response()->json(['status' => 'ignored', 'reason' => 'No message data provided'], 200);
        }

        try {
            $decodedJson = base64_decode($messageData);
            $data = json_decode($decodedJson, true);

            $historyId = $data['historyId'] ?? null;
            $emailAddress = $data['emailAddress'] ?? null;

            if ($historyId) {
                ProcessGmailSyncJob::dispatch((string) $historyId, (string) $emailAddress);
            }

            return response()->json(['status' => 'success', 'historyId' => $historyId], 200);
        } catch (\Throwable $e) {
            Log::error('Error processing Gmail Pub/Sub notification', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 200);
        }
    }
}
