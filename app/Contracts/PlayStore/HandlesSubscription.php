<?php

namespace App\Contracts\PlayStore;

use App\Models\User;
use App\Models\UserReceipt;
use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\Purchases\Events\PurchaseEvent;

interface HandlesSubscription
{
    /**
     * Handle the received purchase event.
     */
    public function handle($event);

    /**
     * Returns the user receipt belonging to the transaction info.
     */
    public function findUserReceipt(?string $userID, string $originalTransactionID): ?UserReceipt;

    /**
     * Finds or creates, and returns a new user receipt.
     */
    public function findOrCreateUserReceipt(DeveloperNotification $developerNotification): UserReceipt;

    /**
     * Notify the user of the changes applied to the subscription.
     */
    public function notifyUserAboutUpdate(?User $user, PurchaseEvent $event);
}
