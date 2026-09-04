<?php

namespace App\Console\Commands;

use App\Services\AbandonedCartService;
use Illuminate\Console\Command;

class DetectAbandonedCarts extends Command
{
    protected $signature = 'carts:detect-abandoned';

    protected $description = 'Open abandoned-cart records for carts that have gone quiet, and close the ones that converted or expired';

    public function handle(AbandonedCartService $service): int
    {
        $result = $service->sync();

        $this->info(sprintf(
            'Abandoned carts: %d opened, %d recovered, %d expired, %d refreshed.',
            $result['detected'],
            $result['recovered'],
            $result['expired'],
            $result['refreshed'],
        ));

        return self::SUCCESS;
    }
}
