<?php

namespace App\Listeners\PlayStore;

use App\Models\UserReceipt;

class OneTimeProductCanceled
{
    public function handle($event): void
    {
        $purchaseToken = $event->purchaseToken;
        $productId = $event->productId;

        $userReceipt = UserReceipt::firstWhere('original_transaction_id', '=', $purchaseToken);

        if (empty($userReceipt)) {
            return;
        }

        $userReceipt->update([
            'is_subscribed' => false,
            'revoked_at' => now(),
        ]);

        $user = $userReceipt->user;

        if (empty($user)) {
            return;
        }

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
