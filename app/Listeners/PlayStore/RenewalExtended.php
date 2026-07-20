<?php

namespace App\Listeners\PlayStore;

use Carbon\Carbon;
use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRenewed;

class RenewalExtended extends PlayStoreListener
{
    /**
     * Notification type: SUBSCRIPTION_RENEWED (2) — erişim uzatıldı.
     *
     * @param  SubscriptionRenewed  $event
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

        $userReceipt = $this->findOrCreateUserReceipt($developerNotification);
        $userReceipt->update([
            'is_subscribed' => true,
            'expired_at' => $expiresDate,
            'revoked_at' => null,
        ]);

        $user = $userReceipt->user;
        $user?->update(['is_subscribed' => true]);

        $this->notifyUserAboutUpdate($user, $event);
    }
}
