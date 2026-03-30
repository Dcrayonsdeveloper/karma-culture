<?php

namespace App\Events;

use App\Models\PosSale;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PosSaleCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PosSale $sale
    ) {}
}
