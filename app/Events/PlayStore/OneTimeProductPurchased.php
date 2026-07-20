<?php

namespace App\Events\PlayStore;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OneTimeProductPurchased
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $purchaseToken;
    public string $productId;

    public function __construct(string $purchaseToken, string $productId)
    {
        $this->purchaseToken = $purchaseToken;
        $this->productId = $productId;
    }
}
