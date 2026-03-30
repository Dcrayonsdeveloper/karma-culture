<?php

namespace App\Events;

use App\Models\OrderReturn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public OrderReturn $return,
        public float $amount,
        public string $method
    ) {}
}
