<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\UserReceipt;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Imdhemy\Purchases\Facades\Product;
use Imdhemy\Purchases\Facades\Subscription;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class GooglePlayPurchaseController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'purchase_token' => 'required|string',
            'product_id' => 'required|string',
            'type' => 'required|string|in:subscription,product',
        ]);

        $purchaseToken = $request->input('purchase_token');
        $productId = $request->input('product_id');
        $type = $request->input('type');
        $user = $request->user();
        $userID = $user->uuid;

        try {
            if ($type === 'subscription') {
                return $this->verifySubscription($purchaseToken, $productId, $userID, $user);
            }

            return $this->verifyOneTimeProduct($purchaseToken, $productId, $userID, $user);
        } catch (Exception $e) {
            logger()->error('Google Play verification failed', [
                'error' => $e->getMessage(),
                'token' => $purchaseToken,
                'type' => $type,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Verification failed'], 422);
        }
    }

    private function verifySubscription(string $purchaseToken, string $productId, string $userID, $user): JsonResponse
    {
        $subscription = Subscription::googlePlay()
            ->id($productId)
            ->token($purchaseToken)
            ->get();

        $expiryTimeMillis = $subscription->getExpiryTimeMillis();
        $startTimeMillis = $subscription->getStartTimeMillis();

        $expiresDate = $expiryTimeMillis ? Carbon::createFromTimestampMs($expiryTimeMillis) : null;
        $purchaseDate = $startTimeMillis ? Carbon::createFromTimestampMs($startTimeMillis) : null;

        $isInGracePeriod = $this->isInGracePeriod($subscription);
        $isSubscriptionValid = ($expiresDate?->isFuture() ?? false) || $isInGracePeriod;
        $willAutoRenew = (bool) $subscription->getAutoRenewing();

        $userReceipt = UserReceipt::firstWhere('original_transaction_id', '=', $purchaseToken);

        if (empty($userReceipt)) {
            UserReceipt::create([
                'user_id' => $userID,
                'original_transaction_id' => $purchaseToken,
                'product_id' => $productId,
                'is_subscribed' => $isSubscriptionValid,
                'will_auto_renew' => $willAutoRenew,
                'original_purchased_at' => $purchaseDate,
                'purchased_at' => $purchaseDate,
                'expired_at' => $expiresDate,
            ]);
        } elseif (empty($userReceipt->user_id)) {
            $userReceipt->user_id = $userID;
            $userReceipt->save();
        }

        $user->update([
            'is_pro' => $isSubscriptionValid,
            'is_subscribed' => $isSubscriptionValid,
            'subscribed_at' => $purchaseDate,
        ]);

        return response()->json(['status' => 'ok']);
    }

    private function verifyOneTimeProduct(string $purchaseToken, string $productId, string $userID, $user): JsonResponse
    {
        $product = Product::googlePlay()
            ->id($productId)
            ->token($purchaseToken)
            ->get();

        if ($product->getPurchaseState() !== 0) {
            throw new ConflictHttpException('Purchase is not in a valid state.');
        }

        if ($product->getAcknowledgementState() !== 1) {
            Product::googlePlay()
                ->id($productId)
                ->token($purchaseToken)
                ->acknowledge();
        }

        $purchaseTimeMillis = $product->getPurchaseTimeMillis();
        $purchaseDate = $purchaseTimeMillis ? Carbon::createFromTimestampMs($purchaseTimeMillis) : null;

        $isConsumable = str_contains($productId, 'Tip'); // in future may requires fix

        if ($isConsumable) {
            Product::googlePlay()
                ->id($productId)
                ->token($purchaseToken)
                ->consume();
        }

        $userReceipt = UserReceipt::firstWhere('original_transaction_id', '=', $purchaseToken);

        if (empty($userReceipt)) {
            UserReceipt::create([
                'user_id' => $userID,
                'original_transaction_id' => $purchaseToken,
                'product_id' => $productId,
                'is_subscribed' => false,
                'will_auto_renew' => false,
                'original_purchased_at' => $purchaseDate,
                'purchased_at' => $purchaseDate,
            ]);
        } elseif (empty($userReceipt->user_id)) {
            $userReceipt->user_id = $userID;
            $userReceipt->save();
        }

        return response()->json(['status' => 'ok']);
    }

    private function isInGracePeriod($subscription): bool
    {
        $paymentState = $subscription->getPaymentState();
        $expiryTimeMillis = $subscription->getExpiryTimeMillis();
        $expiresDate = $expiryTimeMillis ? Carbon::createFromTimestampMs($expiryTimeMillis) : null;

        return $paymentState === 0 && $expiresDate?->isFuture();
    }
}
