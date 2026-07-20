<?php

namespace App\Listeners\PlayStore;

use Carbon\Carbon;
use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRevoked;

class Revoke extends PlayStoreListener
{
    /**
     * Notification type: SUBSCRIPTION_REVOKED (12) — erişim iptali (aile paylaşımı vs.)
     *
     * @param  SubscriptionRevoked  $event
     */
    public function handle($event): void
    {
        $notification = $event->getServerNotification();
        $subscription = $notification->getSubscription();

        /** @var DeveloperNotification $developerNotification */
        $developerNotification = $subscription->getProviderRepresentation();

        /** @var SubscriptionPurchase $subscriptionPurchase */
        $subscriptionPurchase = $developerNotification->getSubscription()->getProviderRepresentation();

        $expiryTimeMillis = $subscriptionPurchase->getExpiryTimeMillis();
        $expiresDate = $expiryTimeMillis ? Carbon::createFromTimestampMs($expiryTimeMillis) : null;

        $cancelledAtMillis = $subscriptionPurchase->getUserCancellationTimeMillis();
        $revokedAt = $cancelledAtMillis ? Carbon::createFromTimestampMs($cancelledAtMillis) : now();

        $userReceipt = $this->findOrCreateUserReceipt($developerNotification);
        $userReceipt->update([
            'is_subscribed' => false,
            'expired_at' => $expiresDate,
            'revoked_at' => $revokedAt,
        ]);

        $user = $userReceipt->user;
        $user?->update(['is_subscribed' => false]);

        $this->notifyUserAboutUpdate($user, $event);
    }
}
