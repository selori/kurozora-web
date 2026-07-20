<?php

namespace App\Listeners\PlayStore;

use App\Models\UserReceipt;
use Carbon\Carbon;
use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionPurchased;

class Subscribed extends PlayStoreListener
{
    /**
     * Handle the received Subscribed event (new subscription purchased).
     *
     * Notification types: SUBSCRIPTION_PURCHASED (1)
     *
     * @param  SubscriptionPurchased  $event
     */
    public function handle($event): void
    {
        $notification = $event->getServerNotification();
        $subscription = $notification->getSubscription();

        /** @var DeveloperNotification $developerNotification */
        $developerNotification = $subscription->getProviderRepresentation();
        $subscriptionNotification = $developerNotification->getSubscriptionNotification();

        // Collect IDs
        $purchaseToken = $subscriptionNotification->getPurchaseToken();
        $productID = $subscriptionNotification->getSubscriptionId();

        /** @var SubscriptionPurchase $subscriptionPurchase */
        $subscriptionPurchase = $developerNotification->getSubscription()->getProviderRepresentation();

        $userID = $subscriptionPurchase->getObfuscatedExternalAccountId()
            ?? $subscriptionPurchase->getObfuscatedExternalProfileId();

        // Collect dates
        $startTimeMillis = $subscriptionPurchase->getStartTimeMillis();
        $expiryTimeMillis = $subscriptionPurchase->getExpiryTimeMillis();

        $purchaseDate = $startTimeMillis ? Carbon::createFromTimestampMs($startTimeMillis) : null;
        $expiresDate = $expiryTimeMillis ? Carbon::createFromTimestampMs($expiryTimeMillis) : null;

        // Validity
        $isInGracePeriod = $this->isInGracePeriod($subscriptionPurchase);
        $isSubscriptionValid = ($expiresDate?->isFuture() ?? false) || $isInGracePeriod;
        $willAutoRenew = (bool) $subscriptionPurchase->getAutoRenewing();

        // Find or create the receipt
        $userReceipt = $this->findUserReceipt($userID, $purchaseToken);

        if (empty($userReceipt)) {
            $userReceipt = UserReceipt::create([
                'user_id' => $userID,
                'original_transaction_id' => $purchaseToken,
                'web_order_line_item_id' => null,
                'offer_id' => null,
                'subscription_group_id' => null,
                'product_id' => $productID,
                'is_subscribed' => $isSubscriptionValid,
                'will_auto_renew' => $willAutoRenew,
                'original_purchased_at' => $purchaseDate,
                'purchased_at' => $purchaseDate,
                'expired_at' => $expiresDate,
                'revoked_at' => null,
            ]);
        } else {
            $userReceipt->update([
                'product_id' => $productID,
                'is_subscribed' => $isSubscriptionValid,
                'will_auto_renew' => $willAutoRenew,
                'purchased_at' => $purchaseDate,
                'expired_at' => $expiresDate,
                'revoked_at' => null,
            ]);
        }

        // Update user values
        $updateUserAttributes = [
            'is_pro' => $isSubscriptionValid,
            'is_subscribed' => $isSubscriptionValid,
        ];

        if (! empty($purchaseDate)) {
            $updateUserAttributes['subscribed_at'] = $purchaseDate;
        }

        $user = $userReceipt->user;
        $user?->update($updateUserAttributes);

        // Notify the user
        $this->notifyUserAboutUpdate($user, $event);
    }
}
