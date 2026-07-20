<?php

namespace App\Listeners\PlayStore;

use App\Models\UserReceipt;
use Carbon\Carbon;
use Exception;
use Imdhemy\Purchases\Facades\Product;

class OneTimeProductPurchased
{
    public function handle($event): void
    {
        $purchaseToken = $event->purchaseToken;
        $productId = $event->productId;

        try {
            $product = Product::googlePlay()
                ->id($productId)
                ->token($purchaseToken)
                ->get();

            if ($product->getPurchaseState() !== 0) {
                return;
            }

            if ($product->getAcknowledgementState() !== 1) {
                Product::googlePlay()
                    ->id($productId)
                    ->token($purchaseToken)
                    ->acknowledge();
            }

            $isConsumable = str_contains($productId, 'Tip');

            if ($isConsumable) {
                Product::googlePlay()
                    ->id($productId)
                    ->token($purchaseToken)
                    ->consume();
            }

            $userID = $product->getObfuscatedExternalAccountId()
                ?? $product->getObfuscatedExternalProfileId();

            if (empty($userID)) {
                return;
            }

            $purchaseTimeMillis = $product->getPurchaseTimeMillis();
            $purchaseDate = $purchaseTimeMillis ? Carbon::createFromTimestampMs($purchaseTimeMillis) : null;

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
        } catch (Exception $e) {
            logger()->error('Failed to handle one-time product purchase notification', [
                'error' => $e->getMessage(),
                'token' => $purchaseToken,
                'sku' => $productId,
            ]);
        }
    }
}
