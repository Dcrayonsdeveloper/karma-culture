<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportProductsFromExcel extends Command
{
    protected $signature = 'products:import-excel
                            {file : Path to the Excel file}
                            {--dry-run : Preview import without writing to database}
                            {--update-stock : Only update stock for existing products}';

    protected $description = 'Import products from ForeverKids Excel stock report';

    // Column indices (0-based) for the stock report
    private const COL_CODE = 0;
    private const COL_NAME = 1;
    private const COL_UNIT = 2;
    private const COL_STOCK = 3;
    private const COL_COST_PRICE = 6;
    private const COL_MRP = 8;
    private const COL_SALES_PRICE = 10;
    private const COL_BARCODE = 16;

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $dryRun = $this->option('dry-run');
        $updateStockOnly = $this->option('update-stock');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $this->info($dryRun ? '🔍 DRY RUN — no changes will be made' : '📦 Starting product import...');
        $this->newLine();

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            $this->error("Failed to read Excel file: {$e->getMessage()}");
            return self::FAILURE;
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $this->info("Sheet: {$sheet->getTitle()} | Total rows: {$highestRow}");

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $bar = $this->output->createProgressBar($highestRow - 4);
        $bar->start();

        // Data starts at row 5 (rows 1-4 are metadata/headers)
        for ($row = 5; $row <= $highestRow; $row++) {
            $bar->advance();

            $code = trim((string) $sheet->getCellByColumnAndRow(self::COL_CODE + 1, $row)->getValue());
            $name = trim((string) $sheet->getCellByColumnAndRow(self::COL_NAME + 1, $row)->getValue());

            // Skip rows with empty name or code
            if (empty($name) || empty($code)) {
                $stats['skipped']++;
                continue;
            }

            // Skip invalid SKU (must contain at least one alphanumeric character)
            if (! preg_match('/[A-Za-z0-9]/', $code)) {
                $stats['skipped']++;
                continue;
            }

            // Clean the SKU
            $sku = preg_replace('/[^A-Za-z0-9\-_.]/', '', $code);
            if (empty($sku)) {
                $stats['skipped']++;
                continue;
            }

            $stock = (int) abs((float) $sheet->getCellByColumnAndRow(self::COL_STOCK + 1, $row)->getValue());
            $costPrice = (float) $sheet->getCellByColumnAndRow(self::COL_COST_PRICE + 1, $row)->getValue();
            $mrp = (float) $sheet->getCellByColumnAndRow(self::COL_MRP + 1, $row)->getValue();
            $salesPrice = (float) $sheet->getCellByColumnAndRow(self::COL_SALES_PRICE + 1, $row)->getValue();
            $barcode = trim((string) $sheet->getCellByColumnAndRow(self::COL_BARCODE + 1, $row)->getValue());

            // Price logic: use sales price if > 0, otherwise use MRP
            $price = $salesPrice > 0 ? $salesPrice : $mrp;

            // Skip if no valid price
            if ($mrp <= 0 && $price <= 0) {
                $stats['skipped']++;
                continue;
            }

            if ($dryRun) {
                $existing = Product::withTrashed()->where('sku', $sku)->exists();
                if ($existing) {
                    $stats['updated']++;
                } else {
                    $stats['created']++;
                }
                continue;
            }

            try {
                $existingProduct = Product::withTrashed()->where('sku', $sku)->first();

                if ($existingProduct) {
                    if ($updateStockOnly) {
                        $existingProduct->update([
                            'stock_quantity' => $stock,
                            'stock_status' => $stock > 0 ? 'in_stock' : 'out_of_stock',
                        ]);
                    } else {
                        $existingProduct->update([
                            'stock_quantity' => $stock,
                            'stock_status' => $stock > 0 ? 'in_stock' : 'out_of_stock',
                            'cost_price' => $costPrice > 0 ? $costPrice : null,
                            'mrp' => $mrp,
                            'price' => $price,
                        ]);
                    }
                    $stats['updated']++;
                } else {
                    $productData = [
                        'name' => $name,
                        'sku' => $sku,
                        'mrp' => $mrp,
                        'price' => $price,
                        'cost_price' => $costPrice > 0 ? $costPrice : null,
                        'stock_quantity' => $stock,
                        'stock_status' => $stock > 0 ? 'in_stock' : 'out_of_stock',
                        'status' => 'draft',
                        'is_active' => false,
                        'short_description' => $name,
                    ];

                    // Set barcode if present and non-empty
                    if (! empty($barcode) && $barcode !== '0') {
                        // Ensure barcode is unique
                        $barcodeExists = Product::withTrashed()->where('barcode', $barcode)->exists();
                        if (! $barcodeExists) {
                            $productData['barcode'] = $barcode;
                        }
                    }

                    Product::create($productData);
                    $stats['created']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->newLine();
                $this->warn("Row {$row} (SKU: {$sku}): {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info($dryRun ? '  DRY RUN SUMMARY' : '  IMPORT SUMMARY');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("  Created:  <info>{$stats['created']}</info>");
        $this->line("  Updated:  <comment>{$stats['updated']}</comment>");
        $this->line("  Skipped:  <comment>{$stats['skipped']}</comment>");
        if ($stats['errors'] > 0) {
            $this->line("  Errors:   <error>{$stats['errors']}</error>");
        }
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($dryRun) {
            $this->newLine();
            $this->comment('Run without --dry-run to perform the actual import.');
        }

        return self::SUCCESS;
    }
}
