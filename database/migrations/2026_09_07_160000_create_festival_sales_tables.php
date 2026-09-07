<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A festival sale: one percentage, many products, applied to the catalogue.
 *
 * The discount is WRITTEN into products.price rather than resolved at read
 * time, and the two snapshot tables below are what make that reversible.
 *
 * The reason is not laziness. A price held outside the price column is
 * invisible to the four things that read it in SQL rather than in PHP:
 * the price-range filter and the "On Sale" facet (App\Support\ProductFilters
 * :253 and :275), the "Biggest Discount" sort (:308), and the home page's
 * price bands (App\Support\ShopFilterCatalogue::derivePriceBands). A shopper
 * filtering under a thousand rupees would not be shown a product the festival
 * had just brought under a thousand rupees. Writing the column keeps all four
 * honest, and keeps Cart::repriceItems() charging the sale price without
 * knowing this feature exists.
 *
 * Variants carry their own concrete price - Admin\ProductController writes one
 * onto every size row on every save - and the product page reads that, not the
 * product's. So sizes are discounted and snapshotted individually or the most
 * looked-at price on the site would keep showing the old figure while the cart
 * charged the new one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('festival_sales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // The one number the admin types. decimal(5,2) holds 0.00-100.00.
            $table->decimal('discount_percent', 5, 2);

            $table->string('banner_path')->nullable();
            $table->string('banner_mobile_path')->nullable();

            // Deliberately NOT starts_at/ends_at. This sale does not merely
            // display a discount, it rewrites products.price, so "the sale is
            // over" has to be an action that puts several hundred prices back -
            // not a timestamp that silently falls into the past. Making that a
            // switch an admin throws keeps the write and the unwrite in one
            // place, both synchronous, both reporting what they moved.
            // Scheduling it is a later feature and would need a command that
            // calls FestivalSaleService::reconcile(), not a nullable column.
            $table->boolean('is_active')->default(false);
            $table->boolean('show_on_home')->default(true);

            $table->timestamps();

            $table->index('is_active');
        });

        // Membership, plus what the product's own price was before we touched
        // it. sale_price is stored as well as original_price so revert can tell
        // "still the price we wrote" from "an admin has edited this since",
        // and leave the second one alone.
        Schema::create('festival_sale_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('festival_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Null until the sale is switched on. A row with applied_at NULL
            // is a product an admin has ticked but whose price we have not
            // touched, which is what an unpublished draft sale looks like.
            $table->decimal('original_price', 12, 2)->nullable();
            $table->decimal('original_mrp', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['festival_sale_id', 'product_id']);
            $table->index('product_id');
        });

        Schema::create('festival_sale_variant_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('festival_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('original_price', 12, 2)->nullable();
            $table->decimal('original_mrp', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['festival_sale_id', 'product_variant_id'], 'fsvp_sale_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('festival_sale_variant_prices');
        Schema::dropIfExists('festival_sale_products');
        Schema::dropIfExists('festival_sales');
    }
};
