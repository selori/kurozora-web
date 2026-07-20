<?php

namespace App\Listeners\PlayStore;

use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionCanceled;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRestarted;

class DidChangeRenewalStatus extends PlayStoreListener
{
    /**
     * Notification types: SUBSCRIPTION_CANCELED (3), SUBSCRIPTION_RESTARTED (7)
     *
     * @param  SubscriptionCanceled|SubscriptionRestarted  $event
     */
    public function handle($event): void
    {
        $notification = $event->getServerNotification();
        $subscription = $notification->getSubscription();

        /** @var DeveloperNotification $developerNotification */
        $developerNotification = $subscription->getProviderRepresentation();

        /** @var SubscriptionPurchase $subscriptionPurchase */
        $subscriptionPurchase = $developerNotification->getSubscription()->getProviderRepresentation();

        $willAutoRenew = (bool) $subscriptionPurchase->getAutoRenewing();

        $userReceipt = $this->findOrCreateUserReceipt($developerNotification);
        $userReceipt->update([
            'will_auto_renew' => $willAutoRenew,
        ]);

        $user = $userReceipt->user;
        $this->notifyUserAboutUpdate($user, $event);
    }
}
