<?php

namespace App\Listeners\PlayStore;

use App\Contracts\PlayStore\HandlesSubscription;
use App\Models\User;
use App\Models\UserReceipt;
use App\Notifications\SubscriptionStatus;
use Carbon\Carbon;
use Exception;
use Imdhemy\GooglePlay\DeveloperNotifications\DeveloperNotification;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Events\PurchaseEvent;

abstract class PlayStoreListener implements HandlesSubscription
{
    /**
     * Handle the received purchase event.
     */
    public function handle($event) {}

    /**
     * Finds the user receipt belonging to the given purchase token (used as
     * the Play Store equivalent of originalTransactionID).
     *
     * @param  string  $originalTransactionID  purchase token
     */
    public function findUserReceipt(?string $userID, string $originalTransactionID): ?UserReceipt
    {
        $receipt = UserReceipt::firstWhere('original_transaction_id', '=', $originalTransactionID);

        // Return receipt if no user ID is provided or no receipt is found
        if (empty($userID) || empty($receipt)) {
            return $receipt;
        }

        // Update the user ID if it's not already set
        // This is a corrective measure for missing user ID in some receipts
        if (empty($receipt->user_id)) {
            $receipt->user_id = $userID;
            $receipt->save();
        }

        // If the receipt belongs to another user, return null
        if ($receipt->user_id != $userID) {
            return null;
        }

        return $receipt;
    }

    /**
     * Finds or creates a user receipt from a Play Store DeveloperNotification.
     *
     * Google Play sends a SubscriptionNotification inside DeveloperNotification.
     * The SubscriptionPurchase (fetched via the Purchases API) carries the
     * expiry time, auto-renew status, etc. — equivalent to AppStore's
     * transactionInfo + renewalInfo combined.
     */
    public function findOrCreateUserReceipt(DeveloperNotification $developerNotification): UserReceipt
    {
        logger()->channel('stack')->debug(print_r(request()->all(), true));
        logger()->channel('stack')->debug(print_r($developerNotification->toArray(), true));

        $subscriptionNotification = $developerNotification->getSubscriptionNotification();

        // purchaseToken is the Play Store equivalent of originalTransactionId
        $purchaseToken = $subscriptionNotification->getPurchaseToken();
        $productID = $subscriptionNotification->getSubscriptionId();

        // obfuscatedExternalAccountId is the Play Store equivalent of appAccountToken
        /** @var SubscriptionPurchase $subscriptionPurchase */
        $subscriptionPurchase = $developerNotification->getSubscription()->getProviderRepresentation();

        $userID = $subscriptionPurchase->getObfuscatedExternalAccountId()
            ?? $subscriptionPurchase->getObfuscatedExternalProfileId();

        if ($userReceipt = $this->findUserReceipt($userID, $purchaseToken)) {
            return $userReceipt;
        }

        // Collect dates (Play Store returns milliseconds — convert to Carbon)
        $startTimeMillis = $subscriptionPurchase->getStartTimeMillis();
        $expiryTimeMillis = $subscriptionPurchase->getExpiryTimeMillis();

        $purchaseDate = $startTimeMillis ? Carbon::createFromTimestampMs($startTimeMillis) : null;
        $expiresDate = $expiryTimeMillis ? Carbon::createFromTimestampMs($expiryTimeMillis) : null;

        try {
            $isInGracePeriod = $this->isInGracePeriod($subscriptionPurchase);
            $isSubscriptionValid = ($expiresDate?->isFuture() ?? false) || $isInGracePeriod;
            $willAutoRenew = (bool) $subscriptionPurchase->getAutoRenewing();
        } catch (Exception $e) {
            $willAutoRenew = false;
            $isSubscriptionValid = false;
        }

        return UserReceipt::create([
            'user_id' => $userID,
            'original_transaction_id' => $purchaseToken,      // purchase token
            'web_order_line_item_id' => null,                // not applicable for Play Store
            'offer_id' => null,                // not applicable for Play Store
            'subscription_group_id' => null,                // not applicable for Play Store
            'product_id' => $productID,
            'is_subscribed' => $isSubscriptionValid,
            'will_auto_renew' => $willAutoRenew,
            'original_purchased_at' => $purchaseDate,
            'purchased_at' => $purchaseDate,
            'expired_at' => $expiresDate,
            'revoked_at' => null,
        ]);
    }

    /**
     * Notify the user of the changes applied to the subscription.
     */
    public function notifyUserAboutUpdate(?User $user, PurchaseEvent $event): void
    {
        if (empty($user)) {
            return;
        }

        $notification = $event->getServerNotification();
        $user->notify(new SubscriptionStatus($notification->getType()));
    }

    /**
     * Whether the subscription is currently in a grace period.
     *
     * Play Store expresses grace period via paymentState === 0 (payment pending)
     * while the subscription has not yet expired.
     */
    public function isInGracePeriod(SubscriptionPurchase $subscriptionPurchase): bool
    {
        // paymentState: 0 = payment pending (grace period), 1 = received, 2 = free trial, 3 = pending deferred
        $paymentState = $subscriptionPurchase->getPaymentState();
        $expiryTimeMillis = $subscriptionPurchase->getExpiryTimeMillis();
        $expiresDate = $expiryTimeMillis ? Carbon::createFromTimestampMs($expiryTimeMillis) : null;

        return $paymentState === 0 && $expiresDate?->isFuture();
    }
}
