<?php

namespace App\Listeners\PlayStore;

use Carbon\Carbon;
use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionOnHold;

class GracePeriodExpired extends PlayStoreListener
{
    /**
     * Notification type: SUBSCRIPTION_ON_HOLD (5) — grace period sona erip askıya alındı.
     *
     * @param  SubscriptionOnHold  $event
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
            'expired_at' => $expiresDate,
        ]);

        $user = $userReceipt->user;
        $user?->update(['is_subscribed' => false]);
        $this->notifyUserAboutUpdate($user, $event);
    }
}
