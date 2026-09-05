<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-made groups of products, with their own page and header link.
 *
 * The storefront's four built-in listings are computed and cannot be assigned
 * to: New In is the newest by date, Bestsellers is by sales count, and
 * Introductory Offer is whatever is discounted. Those stay as they are - they
 * are useful precisely because nobody has to maintain them. This is the
 * hand-picked kind alongside them: the admin makes a collection, ticks the
 * products that belong in it, and decides whether it appears in the header.
 *
 * Separate from categories on purpose. A category is what a product IS, is
 * hierarchical, and drives the breadcrumb; a collection is a shelf someone
 * assembled - a product can be in several, or none, and being in one says
 * nothing about what the product is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();

            // Two separate switches: a collection can be live at its own URL
            // without taking up room in the header, which is what you want for
            // one linked from a banner or a campaign.
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_header')->default(false);

            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'show_in_header', 'position']);
        });

        Schema::create('collection_product', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->primary(['collection_id', 'product_id']);
            $table->index(['product_id', 'collection_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
    }
};
