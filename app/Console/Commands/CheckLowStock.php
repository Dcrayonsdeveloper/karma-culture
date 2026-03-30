<?php

namespace App\Console\Commands;

use App\Mail\LowStockAlert;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckLowStock extends Command
{
    protected $signature = 'stock:check-low';
    protected $description = 'Check for low stock products and send email alert to admin';

    public function handle(): int
    {
        $lowStockProducts = Product::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->get();

        if ($lowStockProducts->isEmpty()) {
            $this->info('No low stock products found.');
            return self::SUCCESS;
        }

        $this->info("Found {$lowStockProducts->count()} low stock product(s).");

        $adminEmail = config('mail.admin_email', config('mail.from.address'));

        Mail::to($adminEmail)->send(new LowStockAlert($lowStockProducts));

        $this->info("Low stock alert sent to {$adminEmail}.");

        return self::SUCCESS;
    }
}
