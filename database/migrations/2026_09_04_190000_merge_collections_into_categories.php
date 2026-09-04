<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Collections stop being their own table and become rows in `categories`.
 *
 * The two were never really two things to the person using the admin - the
 * categories screen has called itself "Collections" end to end for a while
 * (title, heading, "subcollections"), and the sidebar carried the same word
 * twice pointing at two unrelated screens. This makes the code agree with the
 * word: one table, one screen, one idea.
 *
 * `categories` is the survivor, not `collections`, and the margin is not close:
 * categories holds 19 live rows against 3, is the target of three foreign keys
 * (categories.parent_id, products.category_id, category_product.category_id)
 * against one, owns the hierarchy the admin actually wants to edit, and is
 * named by ~50 files. Moving three rows in costs a migration; moving nineteen
 * out would cost the storefront.
 *
 * The pivots collapse the same way. `category_product` already carries "where
 * this product is shown" for every storefront listing - ProductFilters filters
 * through it, not through products.category_id - so `collection_product` is the
 * same relation under a second name, and its rows move across unchanged.
 *
 * A system row is a destination, not a classification: it owns a URL and a
 * hand-picked membership list, and it is never something a product IS. So it
 * carries is_system and is kept out of the tree, the breadcrumbs, the mega
 * menu, the shop facet, the sitemap and the product form's category select.
 * `handle` is what ties it to the page it overrides - matching on name or slug
 * would break the moment somebody edited either.
 */
return new class extends Migration
{
    /** The page each system row overrides. handle => [name, slug]. */
    private const SYSTEM = [
        'new_in' => ['New In', 'new-in'],
        'bestsellers' => ['Bestsellers', 'bestsellers-picks'],
        'deals' => ['Introductory Offer', 'introductory-offer'],
        // New here. /products is computed from the whole catalogue exactly as
        // the other three pages were before they got a row, and this gives it
        // the same opt-in override: tick nothing and it keeps computing.
        'shop_all' => ['Shop All', 'shop-all'],
    ];

    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('handle', 40)->nullable()->unique()->after('slug');
            $table->boolean('is_system')->default(false)->after('handle');
        });

        $hadCollections = Schema::hasTable('collections');
        $existing = $hadCollections
            ? DB::table('collections')->get()->keyBy('handle')
            : collect();

        $now = now();
        $idByHandle = [];

        foreach (self::SYSTEM as $handle => [$name, $slug]) {
            $old = $existing->get($handle);

            // A slug is unique across the table now that the two share one, and
            // a store that happens to have a category called "new-in" must not
            // lose this migration over it.
            $finalSlug = $slug;
            $suffix = 2;
            while (DB::table('categories')->where('slug', $finalSlug)->exists()) {
                $finalSlug = $slug.'-'.$suffix++;
            }

            $id = DB::table('categories')->insertGetId([
                'parent_id' => null,
                'name' => $old->name ?? $name,
                'slug' => $finalSlug,
                'handle' => $handle,
                'is_system' => true,
                'position' => $old->position ?? 0,
                'level' => 0,
                // The Category model maintains path in its creating/created
                // hooks; a raw insert bypasses them, so it is set here.
                'path' => '0',
                'is_active' => $old->is_active ?? true,
                'is_featured' => false,
                'created_at' => $old->created_at ?? $now,
                'updated_at' => $now,
            ]);

            DB::table('categories')->where('id', $id)->update(['path' => (string) $id]);

            $idByHandle[$handle] = $id;

            if ($old) {
                $idByHandle['#'.$old->id] = $id;
            }
        }

        // Move the memberships. insertOrIgnore because category_product is keyed
        // on (product_id, category_id) and a product could already sit on the
        // category that a collection became - not possible today, but a
        // duplicate here would abort the whole migration for no good reason.
        if (Schema::hasTable('collection_product')) {
            DB::table('collection_product')->orderBy('collection_id')->chunk(500, function ($rows) use ($idByHandle) {
                $moved = [];

                foreach ($rows as $row) {
                    $categoryId = $idByHandle['#'.$row->collection_id] ?? null;

                    if ($categoryId === null) {
                        continue;
                    }

                    $moved[] = ['product_id' => $row->product_id, 'category_id' => $categoryId];
                }

                if ($moved !== []) {
                    DB::table('category_product')->insertOrIgnore($moved);
                }
            });
        }

        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
    }

    public function down(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('handle', 40)->nullable()->unique();
            $table->boolean('is_system')->default(false);
            $table->string('description')->nullable();
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

        // Shop All had no collections row before this migration, so it is not
        // put back - rolling back should leave the table as it was found.
        $system = DB::table('categories')->where('is_system', true)->get();

        foreach ($system as $row) {
            if ($row->handle === 'shop_all') {
                continue;
            }

            $collectionId = DB::table('collections')->insertGetId([
                'name' => $row->name,
                'slug' => $row->slug,
                'handle' => $row->handle,
                'is_system' => true,
                'is_active' => $row->is_active,
                'show_in_header' => false,
                'position' => $row->position,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            $back = DB::table('category_product')
                ->where('category_id', $row->id)
                ->pluck('product_id')
                ->map(fn ($productId) => ['collection_id' => $collectionId, 'product_id' => $productId])
                ->all();

            if ($back !== []) {
                DB::table('collection_product')->insertOrIgnore($back);
            }
        }

        DB::table('category_product')
            ->whereIn('category_id', $system->pluck('id'))
            ->delete();

        DB::table('categories')->where('is_system', true)->delete();

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->dropColumn(['handle', 'is_system']);
        });
    }
};
