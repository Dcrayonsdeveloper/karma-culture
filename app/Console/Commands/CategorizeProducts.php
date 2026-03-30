<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class CategorizeProducts extends Command
{
    protected $signature = 'products:categorize {--dry-run : Preview without saving}';
    protected $description = 'Auto-categorize imported products into Baby Clothing, Shop for Boys, Shop for Girls';

    // Category IDs (from database)
    private const SHOP_FOR_GIRLS = 1;
    private const FROCKS_DRESSES = 2;
    private const LEHENGA_SETS = 3;
    private const SHARARA_SETS = 4;
    private const GIRLS_TOPS = 5;
    private const GIRLS_SKIRTS_SHORTS = 6;
    private const GIRLS_NIGHTWEAR = 7;

    private const SHOP_FOR_BOYS = 8;
    private const BOYS_TSHIRTS_SHIRTS = 9;
    private const BOYS_KURTA_SETS = 10;
    private const BOYS_JEANS_TROUSERS = 11;
    private const BOYS_SHERWANI = 12;
    private const BOYS_SHORTS_BERMUDAS = 13;
    private const BOYS_NIGHTWEAR = 14;

    private const BABY_CLOTHING = 15;
    private const ROMPERS_BODYSUITS = 16;
    private const BABY_SETS = 17;
    private const BIBS_ACCESSORIES = 18;
    private const SWADDLES_BLANKETS = 19;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '🔍 DRY RUN — no changes' : '📂 Categorizing products...');

        $products = Product::whereNull('category_id')
            ->where('status', 'draft')
            ->get(['id', 'name']);

        $this->info("Products to categorize: {$products->count()}");

        $stats = [
            'Shop for Girls' => 0,
            'Shop for Boys' => 0,
            'Baby Clothing' => 0,
        ];
        $subStats = [];

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $bar->advance();
            $name = strtoupper($product->name);

            $categoryId = $this->categorize($name);

            if (!$dryRun) {
                $product->update(['category_id' => $categoryId]);
            }

            // Track stats
            if (in_array($categoryId, [self::SHOP_FOR_GIRLS, self::FROCKS_DRESSES, self::LEHENGA_SETS, self::SHARARA_SETS, self::GIRLS_TOPS, self::GIRLS_SKIRTS_SHORTS, self::GIRLS_NIGHTWEAR])) {
                $stats['Shop for Girls']++;
            } elseif (in_array($categoryId, [self::SHOP_FOR_BOYS, self::BOYS_TSHIRTS_SHIRTS, self::BOYS_KURTA_SETS, self::BOYS_JEANS_TROUSERS, self::BOYS_SHERWANI, self::BOYS_SHORTS_BERMUDAS, self::BOYS_NIGHTWEAR])) {
                $stats['Shop for Boys']++;
            } else {
                $stats['Baby Clothing']++;
            }

            $catName = $this->categoryName($categoryId);
            $subStats[$catName] = ($subStats[$catName] ?? 0) + 1;
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  MAIN CATEGORIES');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        foreach ($stats as $cat => $count) {
            $this->line("  {$cat}: <info>{$count}</info>");
        }

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  SUBCATEGORY BREAKDOWN');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        arsort($subStats);
        foreach ($subStats as $cat => $count) {
            $this->line("  {$cat}: <comment>{$count}</comment>");
        }

        return self::SUCCESS;
    }

    private function categorize(string $name): int
    {
        $hasGirl = (bool) preg_match('/\bGIRL\b|\bGIRLS\b/', $name);
        $hasBoy = (bool) preg_match('/\bBOY\b|\bBOYS\b/', $name);
        $hasBaby = (bool) preg_match('/\bBABY\b|\bNB\b|\bNEWBORN\b|\bNEW BORN\b|\bBORN\b|\bBABA\b/', $name);

        // ── Girl-specific clothing (no BOY keyword needed) ──
        if ($this->matchesAny($name, ['FROCK', 'MIDDI', 'MIDDY', 'GOWN'])) {
            return self::FROCKS_DRESSES;
        }
        if ($this->matchesAny($name, ['LEHENGA', 'LEHNGA', 'CHOLI'])) {
            return self::LEHENGA_SETS;
        }
        if ($this->matchesAny($name, ['SHARARA'])) {
            return self::SHARARA_SETS;
        }
        if ($this->matchesAny($name, ['SAREE'])) {
            return self::SHOP_FOR_GIRLS;
        }

        // ── Boy-specific clothing (no GIRL keyword needed) ──
        if ($this->matchesAny($name, ['KURTA', 'DHOTI'])) {
            return $this->matchesAny($name, ['SET', 'PAJAMA', 'PAJAMI']) ? self::BOYS_KURTA_SETS : self::BOYS_KURTA_SETS;
        }
        if ($this->matchesAny($name, ['SHERWANI'])) {
            return self::BOYS_SHERWANI;
        }

        // ── Explicit GIRL products → subcategorize ──
        if ($hasGirl && !$hasBoy) {
            return $this->subcategorizeGirl($name);
        }

        // ── Explicit BOY products → subcategorize ──
        if ($hasBoy && !$hasGirl) {
            return $this->subcategorizeBoy($name);
        }

        // ── Baby-specific products ──
        if ($hasBaby) {
            return $this->subcategorizeBaby($name);
        }

        // ── Romper/bodysuit → Baby ──
        if ($this->matchesAny($name, ['ROMPER', 'BODYSUIT', 'JHABLA'])) {
            return self::ROMPERS_BODYSUITS;
        }

        // ── Swaddles/blankets → Baby ──
        if ($this->matchesAny($name, ['SWADDLE', 'BLANKET', 'MUSLIN'])) {
            return self::SWADDLES_BLANKETS;
        }

        // ── Baby accessories by product type ──
        if ($this->matchesAny($name, ['TEETHER', 'RATTLE', 'NIPPLE', 'FEEDER', 'BOTTLE', 'WIPES', 'SPONCH', 'APRON'])) {
            return self::BIBS_ACCESSORIES;
        }

        // ── Default: Baby Clothing ──
        return self::BABY_CLOTHING;
    }

    private function subcategorizeGirl(string $name): int
    {
        // Tops & T-Shirts
        if ($this->matchesAny($name, ['TOP', 'T-SHIRT', 'TSHIRT', 'T.SHIRT', 'SHIRT', 'KURTI'])) {
            return self::GIRLS_TOPS;
        }
        // Skirts & Shorts
        if ($this->matchesAny($name, ['SKIRT', 'SHORTS', 'SHORT', 'CAPRI'])) {
            return self::GIRLS_SKIRTS_SHORTS;
        }
        // Nightwear
        if ($this->matchesAny($name, ['NIGHT', 'PAJAMA', 'PAJAMI', 'SLEEPER', 'SLEAPER'])) {
            return self::GIRLS_NIGHTWEAR;
        }
        // Lehenga
        if ($this->matchesAny($name, ['LEHENGA', 'LEHNGA', 'CHOLI'])) {
            return self::LEHENGA_SETS;
        }
        // Frocks
        if ($this->matchesAny($name, ['FROCK', 'DRESS', 'GOWN', 'MIDDI', 'MIDDY'])) {
            return self::FROCKS_DRESSES;
        }

        // Default for girl
        return self::SHOP_FOR_GIRLS;
    }

    private function subcategorizeBoy(string $name): int
    {
        // T-Shirts & Shirts
        if ($this->matchesAny($name, ['T-SHIRT', 'TSHIRT', 'T.SHIRT', 'SHIRT'])) {
            return self::BOYS_TSHIRTS_SHIRTS;
        }
        // Jeans & Trousers
        if ($this->matchesAny($name, ['JEANS', 'TROUSER', 'PANT', 'PENT', 'DENIM', 'LOWER'])) {
            return self::BOYS_JEANS_TROUSERS;
        }
        // Shorts & Bermudas
        if ($this->matchesAny($name, ['SHORTS', 'SHORT', 'BERMUDA'])) {
            return self::BOYS_SHORTS_BERMUDAS;
        }
        // Nightwear
        if ($this->matchesAny($name, ['NIGHT', 'PAJAMA', 'PAJAMI', 'SLEEPER', 'SLEAPER'])) {
            return self::BOYS_NIGHTWEAR;
        }
        // Kurta
        if ($this->matchesAny($name, ['KURTA', 'DHOTI'])) {
            return self::BOYS_KURTA_SETS;
        }

        // Default for boy
        return self::SHOP_FOR_BOYS;
    }

    private function subcategorizeBaby(string $name): int
    {
        // Rompers
        if ($this->matchesAny($name, ['ROMPER', 'BODYSUIT', 'JHABLA', 'JUMPSUIT'])) {
            return self::ROMPERS_BODYSUITS;
        }
        // Sets
        if ($this->matchesAny($name, ['SET'])) {
            return self::BABY_SETS;
        }
        // Swaddles & Blankets
        if ($this->matchesAny($name, ['SWADDLE', 'BLANKET', 'MUSLIN', 'TOWEL', 'SHEET', 'PILLOW'])) {
            return self::SWADDLES_BLANKETS;
        }
        // Bibs & Accessories
        if ($this->matchesAny($name, ['BIB', 'MITTEN', 'TEETHER', 'RATTLE', 'BRUSH', 'COMB', 'NIPPLE', 'FEEDER', 'BOTTLE', 'BOOTY', 'SOCKS', 'CAP', 'WIPES', 'SPONCH', 'APRON'])) {
            return self::BIBS_ACCESSORIES;
        }

        return self::BABY_CLOTHING;
    }

    private function matchesAny(string $name, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($name, $kw)) {
                return true;
            }
        }
        return false;
    }

    private function categoryName(int $id): string
    {
        return match ($id) {
            self::SHOP_FOR_GIRLS => 'Shop for Girls (general)',
            self::FROCKS_DRESSES => 'Frocks & Dresses',
            self::LEHENGA_SETS => 'Lehenga Sets',
            self::SHARARA_SETS => 'Sharara Sets',
            self::GIRLS_TOPS => 'Girls: Tops & T-Shirts',
            self::GIRLS_SKIRTS_SHORTS => 'Girls: Skirts & Shorts',
            self::GIRLS_NIGHTWEAR => 'Girls: Nightwear',
            self::SHOP_FOR_BOYS => 'Shop for Boys (general)',
            self::BOYS_TSHIRTS_SHIRTS => 'Boys: T-Shirts & Shirts',
            self::BOYS_KURTA_SETS => 'Boys: Kurta Sets',
            self::BOYS_JEANS_TROUSERS => 'Boys: Jeans & Trousers',
            self::BOYS_SHERWANI => 'Boys: Sherwani Sets',
            self::BOYS_SHORTS_BERMUDAS => 'Boys: Shorts & Bermudas',
            self::BOYS_NIGHTWEAR => 'Boys: Nightwear',
            self::BABY_CLOTHING => 'Baby Clothing (general)',
            self::ROMPERS_BODYSUITS => 'Rompers & Bodysuits',
            self::BABY_SETS => 'Baby Sets',
            self::BIBS_ACCESSORIES => 'Bibs & Accessories',
            self::SWADDLES_BLANKETS => 'Swaddles & Blankets',
            default => 'Unknown',
        };
    }
}
