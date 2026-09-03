<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Topping the two roots up is the whole job, and "top up" is the part that is
 * easy to get wrong: the count has to be reached from whatever is already
 * there, and re-running must not stack a second set on top of the first.
 *
 * The tiles matter for a reason that is not obvious from the command - the
 * storefront renders a category image as `storage/` . image_url, with no URL
 * resolution of any kind, so the column has to hold a disk-relative path. An
 * absolute URL there produces src="storage/https://..." and every tile breaks.
 */
class FashionSubcategoriesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function roots(int $menChildren = 2, int $womenChildren = 1): array
    {
        $men = Category::create(['name' => 'MEN', 'slug' => 'men', 'is_active' => true]);
        $women = Category::create(['name' => 'WOMEN', 'slug' => 'women', 'is_active' => true]);

        for ($i = 1; $i <= $menChildren; $i++) {
            Category::create(['name' => 'SUB-MEN-'.$i, 'slug' => 'sub-men-'.$i, 'parent_id' => $men->id, 'is_active' => true]);
        }
        for ($i = 1; $i <= $womenChildren; $i++) {
            Category::create(['name' => 'SUB-WOMEN-'.$i, 'slug' => 'sub-women-'.$i, 'parent_id' => $women->id, 'is_active' => true]);
        }

        return [$men, $women];
    }

    public function test_it_tops_each_root_up_to_ten_counting_what_is_already_there(): void
    {
        Storage::fake('public');
        [$men, $women] = $this->roots(2, 1);

        $this->artisan('categories:fashion-subcategories')->assertSuccessful();

        $this->assertSame(10, $men->children()->count(), '2 existing + 8 new');
        $this->assertSame(10, $women->children()->count(), '1 existing + 9 new');

        // The placeholders are kept, not replaced - they may carry products,
        // and deleting a category cascades to what is filed under it.
        $this->assertDatabaseHas('categories', ['slug' => 'sub-men-1']);
        $this->assertDatabaseHas('categories', ['slug' => 'sub-women-1']);
    }

    public function test_running_it_twice_does_not_stack_a_second_set(): void
    {
        Storage::fake('public');
        [$men, $women] = $this->roots(2, 1);

        $this->artisan('categories:fashion-subcategories')->assertSuccessful();
        $this->artisan('categories:fashion-subcategories')->assertSuccessful();

        $this->assertSame(10, $men->children()->count());
        $this->assertSame(10, $women->children()->count());
        $this->assertSame(
            Category::count(),
            Category::distinct()->count('slug'),
            'slug is unique-constrained; a duplicate would have thrown before this',
        );
    }

    public function test_every_category_on_both_roots_ends_up_with_a_tile(): void
    {
        Storage::fake('public');
        [$men, $women] = $this->roots(2, 1);

        $this->artisan('categories:fashion-subcategories')->assertSuccessful();

        foreach ([$men, $women] as $root) {
            $root->refresh();
            $this->assertNotNull($root->image_url, 'the root tile shows on /categories too');

            foreach ($root->children()->get() as $child) {
                $this->assertNotNull($child->image_url, $child->slug.' has no tile');
            }
        }
    }

    /**
     * The blade does `asset_v('storage/' . $category->image_url)` and nothing
     * else, so anything absolute in this column renders as storage/https://...
     */
    public function test_the_image_path_is_disk_relative_and_the_file_exists(): void
    {
        Storage::fake('public');
        $this->roots(2, 1);

        $this->artisan('categories:fashion-subcategories')->assertSuccessful();

        foreach (Category::whereNotNull('image_url')->get() as $category) {
            $this->assertStringStartsWith('categories/', $category->image_url);
            $this->assertStringNotContainsString('://', $category->image_url);
            $this->assertStringNotContainsString('storage/', $category->image_url);

            Storage::disk('public')->assertExists($category->image_url);
        }
    }

    public function test_it_does_not_overwrite_an_uploaded_image(): void
    {
        Storage::fake('public');
        [$men] = $this->roots(2, 1);

        $mine = $men->children()->first();
        $mine->update(['image_url' => 'categories/a-real-photo.jpg']);

        $this->artisan('categories:fashion-subcategories')->assertSuccessful();

        $this->assertSame('categories/a-real-photo.jpg', $mine->fresh()->image_url);
    }

    public function test_it_refuses_rather_than_guessing_when_a_root_is_missing(): void
    {
        Storage::fake('public');
        Category::create(['name' => 'MEN', 'slug' => 'men', 'is_active' => true]);

        $this->artisan('categories:fashion-subcategories')->assertFailed();

        // Nothing half-made: the men shelf must not be filled when women is
        // absent, or a re-run after fixing the data would start from a partial
        // state nobody asked for.
        $this->assertSame(1, Category::count());
    }

    public function test_new_subcategories_are_active_and_nested_under_their_root(): void
    {
        Storage::fake('public');
        [$men] = $this->roots(2, 1);

        $this->artisan('categories:fashion-subcategories')->assertSuccessful();

        $shirts = Category::where('slug', 'shirts')->first();

        $this->assertNotNull($shirts);
        $this->assertSame($men->id, $shirts->parent_id);
        $this->assertTrue($shirts->is_active);
        $this->assertSame(1, (int) $shirts->level, 'level is set by the model hook');
        $this->assertSame($men->path.'/'.$shirts->id, $shirts->path);
    }

    /**
     * The slug is regenerated from the name on every update, so it cannot be
     * assigned by hand and made to stick. The names therefore have to carry the
     * uniqueness themselves - one shared between the roots would come back as
     * "jeans" and "jeans-1", and which root got the clean URL would depend on
     * insert order.
     */
    public function test_no_name_is_used_under_both_roots(): void
    {
        Storage::fake('public');
        [$men, $women] = $this->roots(2, 1);

        $this->artisan('categories:fashion-subcategories')->assertSuccessful();

        $menNames = $men->children()->pluck('name');
        $womenNames = $women->children()->pluck('name');

        $this->assertEmpty(
            $menNames->intersect($womenNames)->all(),
            'a shared name would collide on slug',
        );

        // Nothing fell back to a numbered slug.
        foreach ($men->children->concat($women->children) as $child) {
            $this->assertSame(
                \Illuminate\Support\Str::slug($child->name),
                $child->slug,
                $child->name.' did not get the clean slug',
            );
        }
    }
}
