<?php

namespace App\Listeners\PlayStore;

use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionPriceChangeConfirmed;

class DidChangeRenewalPref extends PlayStoreListener
{
    /**
     * Notification type: SUBSCRIPTION_PRICE_CHANGE_CONFIRMED (4) veya plan değişikliği
     *
     * @param  SubscriptionPriceChangeConfirmed  $event
     */
    public function handle($event): void
    {
        $notification = $event->getServerNotification();
        $subscription = $notification->getSubscription();

        /** @var DeveloperNotification $developerNotification */
        $developerNotification = $subscription->getProviderRepresentation();
        $subscriptionNotification = $developerNotification->getSubscriptionNotification();

        $productID = $subscriptionNotification->getSubscriptionId();

        $userReceipt = $this->findOrCreateUserReceipt($developerNotification);
        $userReceipt->update([
            'product_id' => $productID,
        ]);

        $user = $userReceipt->user;
        $this->notifyUserAboutUpdate($user, $event);
    }
}
