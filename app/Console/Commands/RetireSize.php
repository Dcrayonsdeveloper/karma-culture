<?php

namespace App\Console\Commands;

use App\Models\SizePreset;
use App\Services\SizeRetirementService;
use App\Support\ShopFilterCatalogue;
use Illuminate\Console\Command;

/**
 * Take one size off the catalogue by name.
 *
 * The admin screen does this whenever a size is deleted from the library. This
 * is for the sizes deleted BEFORE it did: their library row is already gone,
 * so there is nothing left to press delete on, and the size goes on selling on
 * every product that carries it. Naming the size is the only way back.
 *
 * It is deliberately one size at a time and named explicitly. The obvious
 * alternative - "delete every size the library does not list" - would empty
 * most of the catalogue: XS, 3XL, 32, 36 and the children's sizes are all
 * carried by products without ever having been library rows.
 *
 * @see \App\Console\Commands\NormaliseProductOptionsToLibrary for the sweep
 *      that DOES reconcile the whole catalogue against the libraries.
 */
class RetireSize extends Command
{
    protected $signature = 'sizes:retire
                            {size : The size label to take off every product, e.g. MD}
                            {--apply : Write the changes. Without this the command only reports}';

    protected $description = 'Take one size off every product that carries it, without deleting the products';

    public function handle(SizeRetirementService $sizes): int
    {
        $size = trim((string) $this->argument('size'));
        $apply = (bool) $this->option('apply');

        if ($size === '') {
            $this->error('Give me a size to retire.');

            return self::FAILURE;
        }

        // A size still in the library would come straight back the next time
        // anyone ticks it on a product, so say so rather than quietly working.
        $preset = SizePreset::all()->first(
            fn (SizePreset $p) => ShopFilterCatalogue::normaliseKey($p->name) === ShopFilterCatalogue::normaliseKey($size)
        );

        if ($preset !== null) {
            $this->warn('"'.$preset->name.'" is still in the Sizes library.');
            $this->line('  Delete it under Admin -> Products -> Sizes and the products are cleaned up with it.');

            if (! $this->confirm('Retire it from the products anyway, leaving the library row in place?', false)) {
                return self::FAILURE;
            }
        }

        $report = $apply ? $sizes->retire($size) : $sizes->preview($size);

        $this->table([$apply ? 'Applied' : 'Would apply', 'Count'], [
            ['Products losing the size', $report['products']],
            ['Size rows deleted', $report['variants']],
            ['Basket lines removed', $report['carts']],
            ['Past order lines (kept, untouched)', $report['orders']],
        ]);

        if ($report['locked'] !== []) {
            $this->line('');
            $this->warn('  '.count($report['locked']).' size row(s) kept because a stock transfer refers to them:');

            foreach (array_slice($report['locked'], 0, 25) as $line) {
                $this->line('    '.$line);
            }
        }

        if ($report['variants'] === 0 && $report['locked'] === []) {
            $this->line('');
            $this->info('  Nothing carries "'.$size.'".');

            return self::SUCCESS;
        }

        $this->line('');

        if ($apply) {
            $this->info('  Done. The shop\'s size rail no longer offers "'.$size.'".');
        } else {
            $this->comment('  Nothing was written. Re-run with --apply to commit.');
        }

        return self::SUCCESS;
    }
}
