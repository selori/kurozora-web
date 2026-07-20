<?php

namespace App\Listeners\PlayStore;

use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionPriceChangeConfirmed;

class PriceIncrease extends PlayStoreListener
{
    /**
     * Notification type: SUBSCRIPTION_PRICE_CHANGE_CONFIRMED (4)
     *
     * @param  SubscriptionPriceChangeConfirmed  $event
     */
    public function handle($event): void
    {
        $notification = $event->getServerNotification();
        $subscription = $notification->getSubscription();

        /** @var DeveloperNotification $developerNotification */
        $developerNotification = $subscription->getProviderRepresentation();

        /** @var SubscriptionPurchase $subscriptionPurchase */
        $subscriptionPurchase = $developerNotification->getSubscription()->getProviderRepresentation();

        // Fiyat değişikliği onaylandıysa auto-renew devam ediyor demektir
        $willAutoRenew = (bool) $subscriptionPurchase->getAutoRenewing();

        $userReceipt = $this->findOrCreateUserReceipt($developerNotification);
        $userReceipt->update([
            'will_auto_renew' => $willAutoRenew,
        ]);

        $user = $userReceipt->user;
        $this->notifyUserAboutUpdate($user, $event);
    }
}
