<?php

namespace Tests\API;

use App\Models\UserReceipt;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Imdhemy\GooglePlay\ValueObjects\EmptyResponse;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\ProvidesTestUser;

class GooglePlayPurchaseTest extends TestCase
{
    use DatabaseMigrations, ProvidesTestUser;

    // ─── Validation Tests ───────────────────────────────────────────

    #[Test]
    function verify_requires_purchase_token(): void
    {
        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'product_id' => 'kPlus1Month',
            'type'       => 'subscription',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors' => [['detail']],
        ]);
    }

    #[Test]
    function verify_requires_product_id(): void
    {
        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'test-token',
            'type'           => 'subscription',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors' => [['detail']],
        ]);
    }

    #[Test]
    function verify_requires_type(): void
    {
        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'test-token',
            'product_id'     => 'kPlus1Month',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors' => [['detail']],
        ]);
    }

    #[Test]
    function verify_rejects_invalid_type(): void
    {
        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'test-token',
            'product_id'     => 'kPlus1Month',
            'type'           => 'invalid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors' => [['detail']],
        ]);
    }

    #[Test]
    function verify_requires_auth(): void
    {
        $response = $this->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'test-token',
            'product_id'     => 'kPlus1Month',
            'type'           => 'subscription',
        ]);

        $response->assertStatus(401);
    }

    // ─── Subscription Tests ─────────────────────────────────────────

    #[Test]
    function subscription_verified_successfully(): void
    {
        $this->mockSubscriptionFacade([
            'startTimeMillis'  => now()->subMonth()->valueOf(),
            'expiryTimeMillis' => now()->addMonth()->valueOf(),
            'autoRenewing'     => true,
            'paymentState'     => 1,
            'orderId'          => 'GPA.1234-5678-9012-34567',
        ]);

        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'test-token',
            'product_id'     => 'kPlus1Month',
            'type'           => 'subscription',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('user_receipts', [
            'original_transaction_id' => 'test-token',
            'product_id'              => 'kPlus1Month',
            'is_subscribed'           => true,
            'will_auto_renew'         => true,
        ]);

        $this->user->refresh();
        $this->assertTrue($this->user->is_subscribed);
        $this->assertTrue($this->user->is_pro);
    }

    #[Test]
    function expired_subscription_not_verified(): void
    {
        $this->mockSubscriptionFacade([
            'startTimeMillis'  => now()->subMonths(2)->valueOf(),
            'expiryTimeMillis' => now()->subMonth()->valueOf(),
            'autoRenewing'     => false,
            'paymentState'     => 1,
        ]);

        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'expired-token',
            'product_id'     => 'kPlus1Month',
            'type'           => 'subscription',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('user_receipts', [
            'original_transaction_id' => 'expired-token',
            'is_subscribed'           => false,
        ]);

        $this->user->refresh();
        $this->assertFalse($this->user->is_subscribed);
        $this->assertFalse($this->user->is_pro);
    }

    #[Test]
    function subscription_in_grace_period_is_valid(): void
    {
        $this->mockSubscriptionFacade([
            'startTimeMillis'  => now()->subMonth()->valueOf(),
            'expiryTimeMillis' => now()->addDays(3)->valueOf(),
            'autoRenewing'     => true,
            'paymentState'     => 0,
        ]);

        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'grace-token',
            'product_id'     => 'kPlus1Month',
            'type'           => 'subscription',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('user_receipts', [
            'original_transaction_id' => 'grace-token',
            'is_subscribed'           => true,
        ]);

        $this->user->refresh();
        $this->assertTrue($this->user->is_subscribed);
    }

    // ─── One-Time Product Tests ─────────────────────────────────────

    #[Test]
    function one_time_product_verified_successfully(): void
    {
        $this->mockProductFacade([
            'purchaseState'        => 0,
            'consumptionState'     => 0,
            'acknowledgementState' => 0,
            'purchaseTimeMillis'   => now()->valueOf(),
            'orderId'              => 'GPA.9999-8888-7777',
        ]);

        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'product-token',
            'product_id'     => 'kurozoraOne',
            'type'           => 'product',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('user_receipts', [
            'original_transaction_id' => 'product-token',
            'product_id'              => 'kurozoraOne',
        ]);
    }

    #[Test]
    function consumable_product_consumed_after_verification(): void
    {
        $mockProduct = Mockery::mock(\Imdhemy\Purchases\Product::class);
        $mockProductPurchase = new \Imdhemy\GooglePlay\Products\ProductPurchase([
            'purchaseState'        => 0,
            'consumptionState'     => 0,
            'acknowledgementState' => 0,
            'purchaseTimeMillis'   => now()->valueOf(),
        ]);

        $mockProduct->shouldReceive('googlePlay')->andReturnSelf();
        $mockProduct->shouldReceive('id')->andReturnSelf();
        $mockProduct->shouldReceive('token')->andReturnSelf();
        $mockProduct->shouldReceive('get')->andReturn($mockProductPurchase);
        $mockProduct->shouldReceive('acknowledge')->andReturn($this->mockEmptyResponse());
        $mockProduct->shouldReceive('consume')->andReturn($this->mockEmptyResponse());

        $this->app->instance('product', $mockProduct);

        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'wolf-tip-token',
            'product_id'     => 'wolfTip',
            'type'           => 'product',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    function invalid_purchase_state_returns_error(): void
    {
        $this->mockProductFacade([
            'purchaseState'        => 1,
            'consumptionState'     => 0,
            'acknowledgementState' => 0,
            'purchaseTimeMillis'   => now()->valueOf(),
        ]);

        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'canceled-token',
            'product_id'     => 'kurozoraOne',
            'type'           => 'product',
        ]);

        $response->assertStatus(422);
    }

    // ─── Duplicate / Ownership Tests ────────────────────────────────

    #[Test]
    function duplicate_token_updates_existing_receipt_owner(): void
    {
        UserReceipt::create([
            'user_id'                 => $this->user->uuid,
            'original_transaction_id' => 'existing-token',
            'product_id'              => 'kPlus1Month',
            'is_subscribed'           => false,
            'will_auto_renew'         => false,
            'original_purchased_at'   => now(),
            'purchased_at'            => now(),
        ]);

        $this->mockSubscriptionFacade([
            'startTimeMillis'  => now()->subMonth()->valueOf(),
            'expiryTimeMillis' => now()->addMonth()->valueOf(),
            'autoRenewing'     => true,
            'paymentState'     => 1,
        ]);

        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'existing-token',
            'product_id'     => 'kPlus1Month',
            'type'           => 'subscription',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_receipts', [
            'original_transaction_id' => 'existing-token',
            'is_subscribed'           => false,
            'will_auto_renew'         => false,
        ]);
    }

    // ─── Google Play API Error Handling ─────────────────────────────

    #[Test]
    function api_exception_returns_friendly_error(): void
    {
        $mock = Mockery::mock(\Imdhemy\Purchases\Subscription::class);
        $mock->shouldReceive('googlePlay')->andReturnSelf();
        $mock->shouldReceive('id')->andReturnSelf();
        $mock->shouldReceive('token')->andReturnSelf();
        $mock->shouldReceive('get')->andThrow(new \Exception('Google API failure'));

        $this->app->instance('subscription', $mock);

        $response = $this->auth()->json('POST', 'v1/store/purchases/google-play/verify', [
            'purchase_token' => 'error-token',
            'product_id'     => 'kPlus1Month',
            'type'           => 'subscription',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error', 'message' => 'Verification failed']);
    }

    private function mockSubscriptionFacade(array $responseData, ?MockInterface $customMock = null): MockInterface
    {
        $mockSubscriptionPurchase = new \Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase($responseData);
        $mock = $customMock ?? Mockery::mock(\Imdhemy\Purchases\Subscription::class);
        $mock->shouldReceive('googlePlay')->andReturnSelf();
        $mock->shouldReceive('id')->andReturnSelf();
        $mock->shouldReceive('token')->andReturnSelf();
        $mock->shouldReceive('get')->andReturn($mockSubscriptionPurchase);

        $this->app->instance('subscription', $mock);

        return $mock;
    }

    private function mockEmptyResponse(): \Imdhemy\GooglePlay\ValueObjects\EmptyResponse
    {
        return new \Imdhemy\GooglePlay\ValueObjects\EmptyResponse(
            new \GuzzleHttp\Psr7\Response(200)
        );
    }

    private function mockProductFacade(array $responseData, ?MockInterface $customMock = null): MockInterface
    {
        $mockProductPurchase = new \Imdhemy\GooglePlay\Products\ProductPurchase($responseData);
        $mock = $customMock ?? Mockery::mock(\Imdhemy\Purchases\Product::class);
        $mock->shouldReceive('googlePlay')->andReturnSelf();
        $mock->shouldReceive('id')->andReturnSelf();
        $mock->shouldReceive('token')->andReturnSelf();
        $mock->shouldReceive('get')->andReturn($mockProductPurchase);
        $mock->shouldReceive('acknowledge')->andReturn($this->mockEmptyResponse());
        $mock->shouldReceive('consume')->andReturn($this->mockEmptyResponse());

        $this->app->instance('product', $mock);

        return $mock;
    }
}
