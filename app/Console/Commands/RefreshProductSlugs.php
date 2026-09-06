<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductSlugRedirect;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bring product URLs back in line with product names.
 *
 * The catalogue was imported from an outside source, and the product names were
 * later corrected with raw `UPDATE products SET name = REPLACE(...)` statements.
 * Raw SQL goes around Eloquent, so the model's slug generation never ran and
 * every slug still spells the name the product was imported under - a page
 * titled one thing, living at an address that says another, with that address
 * published in the sitemap.
 *
 * This regenerates each slug from the current name and files the old one in
 * product_slug_redirects, so the addresses already indexed answer 301 instead
 * of 404. Reports only unless --apply is passed: rewriting live URLs is not
 * something to do as a side effect of running a command to see what it would do.
 */
class RefreshProductSlugs extends Command
{
    protected $signature = 'products:refresh-slugs
                            {--apply : Write the new slugs. Without this the command only reports.}
                            {--limit= : Only process this many products, for a cautious first run.}';

    protected $description = 'Regenerate product slugs from their current names, preserving old URLs as 301 redirects';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;

        if (! $apply) {
            $this->warn('Dry run. Nothing is written. Re-run with --apply to make these changes.');
        }

        $changed = 0;
        $examined = 0;
        $samples = [];

        $query = Product::query()->orderBy('id');

        $query->chunkById(200, function ($products) use (&$changed, &$examined, &$samples, $apply, $limit) {
            foreach ($products as $product) {
                if ($limit !== null && $examined >= $limit) {
                    return false;
                }

                $examined++;

                $oldSlug = (string) $product->slug;

                // Ask the model itself rather than reimplementing Str::slug here,
                // so a slug made by this command is identical to one the app
                // would have made on an ordinary save.
                $product->generateSlug();
                $newSlug = (string) $product->slug;

                // A name that slugs to nothing would take the product off the
                // site entirely. Leave it alone and let it show up in the count.
                if ($newSlug === '' || $newSlug === $oldSlug) {
                    $product->slug = $oldSlug;

                    continue;
                }

                $changed++;

                if (count($samples) < 5) {
                    $samples[] = [$oldSlug, $newSlug];
                }

                if (! $apply) {
                    $product->slug = $oldSlug;

                    continue;
                }

                DB::transaction(function () use ($product, $oldSlug, $newSlug) {
                    $product->save();

                    // The address being retired now points at this product.
                    ProductSlugRedirect::updateOrCreate(
                        ['old_slug' => $oldSlug],
                        ['product_id' => $product->id],
                    );

                    // An older address that used to lead here by way of the slug
                    // we just retired would otherwise bounce through a dead row.
                    // Point every hop at the product directly.
                    ProductSlugRedirect::where('product_id', $product->id)
                        ->where('old_slug', $newSlug)
                        ->delete();
                });
            }

            return true;
        });

        $this->newLine();
        $this->line("Examined:      {$examined}");
        $this->line("Needing change: {$changed}");

        foreach ($samples as [$old, $new]) {
            $this->line("  {$old}");
            $this->line("    -> {$new}");
        }

        if ($changed > 0 && $apply) {
            $this->newLine();
            $this->info("Rewrote {$changed} slugs. Old addresses now answer 301.");
            $this->warn('Regenerate the sitemap so it advertises the new URLs.');
        }

        return self::SUCCESS;
    }
}
