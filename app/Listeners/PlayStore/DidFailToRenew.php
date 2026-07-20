<?php

namespace App\Listeners\PlayStore;

use Carbon\Carbon;
use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionInGracePeriod;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionOnHold;

class DidFailToRenew extends PlayStoreListener
{
    /**
     * Handle the subscription-on-hold (failed to renew) event.
     *
     * Notification types:
     *   SUBSCRIPTION_ON_HOLD (5)     — payment failed, subscription suspended
     *   SUBSCRIPTION_IN_GRACE_PERIOD (6) — payment failed, grace period active
     *
     * @param  SubscriptionOnHold|SubscriptionInGracePeriod  $event
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

        $isInGracePeriod = $this->isInGracePeriod($subscriptionPurchase);
        $isSubscriptionValid = ($expiresDate?->isFuture() ?? false) || $isInGracePeriod;

        // Find or create the user receipt
        $userReceipt = $this->findOrCreateUserReceipt($developerNotification);
        $userReceipt->update([
            'is_subscribed' => $isSubscriptionValid,
            'expired_at' => $expiresDate,
        ]);

        // Update user values
        $user = $userReceipt->user;
        $user?->update(['is_subscribed' => $isSubscriptionValid]);

        // Notify the user
        $this->notifyUserAboutUpdate($user, $event);
    }
}
