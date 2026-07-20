<?php

namespace App\Listeners\PlayStore;

use Carbon\Carbon;
use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRenewed;

class DidRenew extends PlayStoreListener
{
    /**
     * Handle the received subscription renewal event.
     *
     * Notification types: SUBSCRIPTION_RENEWED (2)
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

        // Find or create the user receipt
        $userReceipt = $this->findOrCreateUserReceipt($developerNotification);
        $userReceipt->update([
            'is_subscribed' => true,
            'will_auto_renew' => (bool) $subscriptionPurchase->getAutoRenewing(),
            'expired_at' => $expiresDate,
            'revoked_at' => null,
        ]);

        // Update user values
        $user = $userReceipt->user;
        $user?->update(['is_subscribed' => true]);

        // Notify the user
        $this->notifyUserAboutUpdate($user, $event);
    }
}
