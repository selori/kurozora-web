<?php

namespace App\Http\Controllers\API\v1;

use App\Events\PlayStore\OneTimeProductCanceled;
use App\Events\PlayStore\OneTimeProductPurchased;
use App\Http\Controllers\Controller;
use App\Models\UserReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlayNotificationController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $message = $request->input('message');

        if (empty($message) || empty($message['data'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid notification'], 400);
        }

        $decoded = json_decode(base64_decode($message['data']), true);

        if (empty($decoded)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        if (isset($decoded['testNotification'])) {
            Log::info('Google Play test notification received', [
                'version' => $decoded['testNotification']['version'] ?? 'unknown',
            ]);

            return response()->json(['status' => 'ok']);
        }

        if (isset($decoded['subscriptionNotification'])) {
            Http::post(route('liap.serverNotifications', ['provider' => 'google-play']), $request->all());

            return response()->json(['status' => 'ok']);
        }

        if (isset($decoded['oneTimeProductNotification'])) {
            $this->handleOneTimeProductNotification($decoded['oneTimeProductNotification']);

            return response()->json(['status' => 'ok']);
        }

        if (isset($decoded['voidedPurchaseNotification'])) {
            $this->handleVoidedPurchaseNotification($decoded['voidedPurchaseNotification']);

            return response()->json(['status' => 'ok']);
        }

        Log::warning('Google Play unknown notification type', ['data' => $decoded]);

        return response()->json(['status' => 'ok']);
    }

    private function handleOneTimeProductNotification(array $notification): void
    {
        $notificationType = $notification['notificationType'] ?? 0;
        $purchaseToken = $notification['purchaseToken'] ?? '';
        $productId = $notification['sku'] ?? '';

        if (empty($purchaseToken) || empty($productId)) {
            Log::warning('Google Play one-time product notification missing fields', $notification);

            return;
        }

        if ($notificationType === 1) {
            event(new OneTimeProductPurchased($purchaseToken, $productId));
        } elseif ($notificationType === 2) {
            event(new OneTimeProductCanceled($purchaseToken, $productId));
        }
    }

    private function handleVoidedPurchaseNotification(array $notification): void
    {
        $purchaseToken = $notification['purchaseToken'] ?? '';

        if (empty($purchaseToken)) {
            return;
        }

        $userReceipt = UserReceipt::firstWhere('original_transaction_id', '=', $purchaseToken);

        if (empty($userReceipt)) {
            return;
        }

        $userReceipt->update([
            'is_subscribed' => false,
            'revoked_at' => now(),
        ]);

        $user = $userReceipt->user;

        if (! empty($user)) {
            $hasActiveSubscription = $user->receipts()
                ->where('is_subscribed', true)
                ->where('id', '!=', $userReceipt->id)
                ->exists();

            if (! $hasActiveSubscription) {
                $user->update([
                    'is_subscribed' => false,
                    'is_pro' => false,
                ]);
            }
        }
    }
}
