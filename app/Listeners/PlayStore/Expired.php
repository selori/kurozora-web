<?php

namespace App\Listeners\PlayStore;

use Carbon\Carbon;
use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionExpired;

class Expired extends PlayStoreListener
{
    /**
     * Handle the received subscription expiry event.
     *
     * Notification types: SUBSCRIPTION_EXPIRED (13)
     *
     * @param  SubscriptionExpired  $event
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

        $isSubscriptionValid = $expiresDate?->isFuture() ?? false;

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
