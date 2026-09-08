<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The product page opens on the shade it has selected.
 *
 * The colour and texture pickers pre-select the first of each list, so the very
 * first paint of a tagged product is already filtered - which means the server,
 * not a JavaScript watcher, has to decide which photograph leads. Get that
 * wrong and the page renders the Rust photograph, cloaks it, and swaps to
 * Indigo the moment Alpine boots.
 *
 * Three things are asserted here because all three are load-bearing and none of
 * them had any test cover before: the tags reach the page at all, the opening
 * index is the right one, and an untagged product is left exactly as it was.
 */
class GalleryFollowsColourTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        $category = Category::create(['name' => 'Womens', 'slug' => 'womens', 'is_active' => true]);

        $product = Product::create([
            'name' => 'Block Print Kurti',
            'slug' => 'block-print-kurti',
            'sku' => 'KURTI-1',
            'description' => 'A kurti.',
            'price' => 1499,
            'mrp' => 1999,
            'stock_quantity' => 10,
            'category_id' => $category->id,
            'is_active' => true,
            'attributes' => $attributes,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'M',
            'sku' => 'KURTI-1-M',
            'price' => 1499,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        return $product;
    }

    private function image(Product $product, int $position, ?string $colour = null, ?string $texture = null, bool $primary = false): ProductImage
    {
        return ProductImage::create([
            'product_id' => $product->id,
            'media_type' => 'image',
            'url' => '/storage/products/shot-'.$position.'.webp',
            'colour' => $colour,
            'texture' => $texture,
            'position' => $position,
            'is_primary' => $primary,
        ]);
    }

    public function test_the_page_opens_on_a_photo_of_the_shade_it_has_selected(): void
    {
        // Indigo is first in the list, so the picker opens on Indigo - but the
        // Rust photograph sorts first and is the one marked main. Neither of
        // those may decide what the shopper sees.
        $product = $this->product([
            'Colours' => [
                ['name' => 'Indigo', 'hex' => '#2b3a67'],
                ['name' => 'Rust', 'hex' => '#b7410e'],
            ],
        ]);

        $this->image($product, 1, 'Rust', primary: true);
        $this->image($product, 2, 'Indigo');

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString(
            'currentImage: 1,',
            $html,
            'The gallery must open on index 1 - the Indigo photograph - because Indigo is the shade the colour picker opens on.'
        );
        $this->assertStringContainsString(
            'mainIndex: 0,',
            $html,
            'The main photo is still index 0. Where the gallery opens and which photo the admin marked as main are two separate questions once photos are tagged.'
        );
    }

    public function test_the_tags_reach_the_browser_normalised(): void
    {
        $product = $this->product([
            'Colours' => [['name' => 'Indigo', 'hex' => '#2b3a67']],
            'Textures' => ['Linen'],
        ]);

        $this->image($product, 1, 'Indigo', 'Linen');
        $this->image($product, 2);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        // Blade's @json compiles to json_encode with JSON_PARTIAL_OUTPUT_ON_ERROR
        // and nothing else, so this reaches the script block as raw JSON.
        $this->assertStringContainsString('"colour":"indigo"', $html);
        $this->assertStringContainsString('"texture":"linen"', $html);
        $this->assertStringContainsString(
            'galleryTagged: true',
            $html,
            'The reactive gallery only switches on for a product somebody has tagged.'
        );
    }

    public function test_a_tag_naming_a_shade_the_product_no_longer_offers_is_ignored(): void
    {
        // Moss was taken off the product but a photograph still names it. A
        // tagged photo shows only when its tag is selected, so honouring this
        // would hide that photograph from the shop for good.
        $product = $this->product([
            'Colours' => [['name' => 'Indigo', 'hex' => '#2b3a67']],
        ]);

        $this->image($product, 1, 'Moss');

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString(
            '"colour":null',
            $html,
            'The stale tag is dropped, which turns the photo back into a shared one rather than making it unreachable.'
        );
        $this->assertStringContainsString(
            'galleryTagged: false',
            $html,
            'With its only tag discarded the product has nothing tagged, so the gallery keeps its original behaviour.'
        );
    }

    public function test_an_untagged_product_keeps_the_gallery_it_always_had(): void
    {
        $product = $this->product([
            'Colours' => [['name' => 'Indigo', 'hex' => '#2b3a67']],
        ]);

        $this->image($product, 1, primary: true);
        $this->image($product, 2);
        $this->image($product, 3);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('galleryTagged: false', $html);
        $this->assertStringContainsString(
            'currentImage: 0,',
            $html,
            'Nothing is tagged, so the main photo leads exactly as it did before any of this existed.'
        );
    }

    public function test_a_shade_with_no_photographs_of_its_own_shows_the_whole_strip(): void
    {
        // Amber is first, so the page opens on it, and every photograph on the
        // product is of the Indigo. Nothing matches - and the answer to that is
        // the whole strip, not an empty frame with an empty rail beside it.
        $product = $this->product([
            'Colours' => [
                ['name' => 'Amber', 'hex' => '#c98a2b'],
                ['name' => 'Indigo', 'hex' => '#2b3a67'],
            ],
        ]);

        $this->image($product, 1, 'Indigo', primary: true);
        $this->image($product, 2, 'Indigo');
        $this->image($product, 3, 'Indigo');

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        // Every thumbnail is painted with no display:none, because the server's
        // visibleIndices() falls back to all three.
        $this->assertSame(
            3,
            substr_count($html, 'x-show="isVisible('),
            'All three thumbnails are still rendered.'
        );
        $this->assertStringNotContainsString(
            'x-show="isVisible(0)" style="display: none;"',
            $html,
            'The server must not hide a thumbnail the fallback rule says to show.'
        );

        // And the browser has to agree, or the rail is drawn and then emptied
        // the moment Alpine boots. isVisible() reads the same list the arrows
        // and the counter walk rather than re-deciding for itself.
        $this->assertStringContainsString(
            'isVisible(index) { return this.visibleImages.includes(index); }',
            $html,
            'isVisible() must answer from visibleImages - which carries the fallback - not from kkMatches(), which does not.'
        );
    }

    public function test_the_gallery_is_told_which_picker_moved(): void
    {
        // kkFollowSelection's three rules are all about the value that just
        // changed - stay put if the frame already shows it, otherwise go to the
        // first frame that does, otherwise leave the frame alone. A handler that
        // only knows "something changed" can express none of them, and the one
        // that did picked any tagged frame: tapping through the fabrics of a
        // product tagged by shade snapped back to the first shade photo, and
        // tapping a shade could settle on a fabric close-up.
        $product = $this->product([
            'Colours' => [['name' => 'Indigo', 'hex' => '#2b3a67']],
            'Textures' => ['Linen'],
        ]);

        $this->image($product, 1, 'Indigo');

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString("this.kkFollowSelection('colour')", $html);
        $this->assertStringContainsString("this.kkFollowSelection('texture')", $html);
        $this->assertStringContainsString(
            'this.mediaTags[i][key] === want',
            $html,
            'The frame it moves to must be one tagged on the dimension that changed, not merely one tagged with something.'
        );
    }

    public function test_a_shade_whose_name_carries_an_apostrophe_can_still_be_picked(): void
    {
        // Blade escapes ' to &#039; and the HTML parser hands Alpine back a
        // literal one, which closes a single-quoted JS string early and makes
        // the whole expression a syntax error - a swatch that does nothing when
        // pressed. Harmless when it only set a variable; now that the gallery
        // follows that variable, a dead swatch is a gallery that cannot move.
        $product = $this->product([
            'Colours' => [['name' => "Sailor's Blue", 'hex' => '#2b3a67']],
        ]);

        $this->image($product, 1, "Sailor's Blue");

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        // Blade's @js hex-escapes the apostrophe INSIDE a single-quoted JS
        // string, which is what makes it safe in a double-quoted HTML
        // attribute. @json would have emitted a bare " and ended the attribute
        // itself - on every colour, not just this one.
        $this->assertStringContainsString(
            "selectedColor = 'Sailor",
            $html,
            'The swatch must still assign the name.'
        );
        $this->assertStringContainsString(
            "selectedColor = 'Sailor\\u0027s Blue'",
            $html,
            'The apostrophe must reach the browser hex-escaped inside a single-quoted JS string. Bare @json would have emitted a double quote and ended the HTML attribute itself - on every colour, not just this one.'
        );
    }

    public function test_a_product_with_no_images_still_renders(): void
    {
        $product = $this->product([
            'Colours' => [['name' => 'Indigo', 'hex' => '#2b3a67']],
        ]);

        $this->get(route('product.show', $product))
            ->assertOk()
            ->assertSee('currentImage: 0,', false);
    }
}
