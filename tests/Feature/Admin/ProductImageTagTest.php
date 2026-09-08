<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Saying which shade and which fabric each photograph shows.
 *
 * Tagged with the rest of the form rather than by an AJAX call per tile, which
 * is what the drag-to-reorder and Make-main controls in the same card do. The
 * difference is deliberate and this file is where it is pinned down: a tag names
 * a colour BY NAME, and the list of colours the product offers is itself only
 * committed when the form is submitted, so tagging a photo "Indigo" in the same
 * save that adds Indigo has to be one request or the tag lands against a colour
 * that does not exist and the photograph goes dark.
 */
class ProductImageTagTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Womens', 'slug' => 'womens', 'is_active' => true]);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Admin', 'role' => 'admin']);
        Admin::create(['user_id' => $user->id, 'role' => 'super_admin', 'is_active' => true]);

        return $user;
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Block Print Kurti',
            'slug' => 'block-print-kurti',
            'sku' => 'KURTI-1',
            'description' => 'A kurti.',
            'price' => 1499,
            'mrp' => 1999,
            'stock_quantity' => 10,
            'category_id' => $this->category->id,
            'is_active' => true,
            'attributes' => [
                'Colours' => [['name' => 'Indigo', 'hex' => '#2b3a67']],
                'Textures' => ['Linen'],
            ],
        ]);
    }

    private function image(Product $product, int $position = 1): ProductImage
    {
        return ProductImage::create([
            'product_id' => $product->id,
            'media_type' => 'image',
            'url' => '/storage/products/shot-'.$position.'.webp',
            'position' => $position,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Block Print Kurti',
            'slug' => 'block-print-kurti',
            'sku' => 'KURTI-1',
            'description' => 'A kurti.',
            'price' => 1499,
            'mrp' => 1999,
            'stock_quantity' => 10,
            'category_ids' => [$this->category->id],
            'seller_id' => '',
            'brand_id' => '',
            'is_active' => 1,
            // Both are required by the product form, so a payload without them
            // never reaches anything this file is about.
            'variants' => [
                ['name' => 'M', 'price' => 1499, 'stock_quantity' => 5, 'sku' => '', 'is_active' => 1],
            ],
            'colours' => [['name' => 'Indigo', 'hex' => '#2b3a67']],
            'textures' => ['Linen'],
        ], $overrides);
    }

    public function test_a_photo_can_be_told_which_shade_and_fabric_it_shows(): void
    {
        $product = $this->product();
        $image = $this->image($product);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.products.update', $product), $this->payload([
                'image_tags' => [$image->id => ['colour' => 'Indigo', 'texture' => 'Linen']],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $image->refresh();

        $this->assertSame('Indigo', $image->colour);
        $this->assertSame('Linen', $image->texture);
    }

    public function test_a_tag_is_stored_the_way_the_product_spells_it(): void
    {
        $product = $this->product();
        $image = $this->image($product);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.products.update', $product), $this->payload([
                'image_tags' => [$image->id => ['colour' => '  indigo ', 'texture' => 'LINEN']],
            ]))
            ->assertSessionHasNoErrors();

        $image->refresh();

        $this->assertSame(
            'Indigo',
            $image->colour,
            'The tag is canonicalised to the product\'s own spelling, so what is stored matches what the picker will offer on sight and not only after normalising.'
        );
        $this->assertSame('Linen', $image->texture);
    }

    public function test_a_shade_the_product_does_not_offer_is_cleared_rather_than_kept(): void
    {
        $product = $this->product();
        $image = $this->image($product);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.products.update', $product), $this->payload([
                'image_tags' => [$image->id => ['colour' => 'Moss', 'texture' => '']],
            ]))
            ->assertSessionHasNoErrors();

        $image->refresh();

        $this->assertNull(
            $image->colour,
            'A tagged photo shows only when its tag is selected, so a tag no picker can produce would hide that photograph from the shop for good while it went on looking present in the admin grid.'
        );
    }

    public function test_a_shade_added_in_the_same_save_can_be_tagged_with(): void
    {
        $product = $this->product();
        $image = $this->image($product);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.products.update', $product), $this->payload([
                'colours' => [
                    ['name' => 'Indigo', 'hex' => '#2b3a67'],
                    ['name' => 'Rust', 'hex' => '#b7410e'],
                ],
                'image_tags' => [$image->id => ['colour' => 'Rust', 'texture' => '']],
            ]))
            ->assertSessionHasNoErrors();

        $image->refresh();

        $this->assertSame(
            'Rust',
            $image->colour,
            'The tags are checked against the colours THIS request committed, not the ones in the database a moment ago - otherwise adding a shade and tagging a photo with it could never be one save.'
        );
    }

    public function test_a_tag_can_be_taken_off_again(): void
    {
        $product = $this->product();
        $image = $this->image($product);
        $image->forceFill(['colour' => 'Indigo', 'texture' => 'Linen'])->save();

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.products.update', $product), $this->payload([
                'image_tags' => [$image->id => ['colour' => '', 'texture' => '']],
            ]))
            ->assertSessionHasNoErrors();

        $image->refresh();

        $this->assertNull($image->colour, 'Choosing "Any shade" turns the photo back into a shared one.');
        $this->assertNull($image->texture);
    }

    public function test_another_products_photo_cannot_be_retagged(): void
    {
        $product = $this->product();

        $other = Product::create([
            'name' => 'Other', 'slug' => 'other', 'sku' => 'OTHER', 'description' => 'x',
            'price' => 100, 'mrp' => 100, 'stock_quantity' => 1,
            'category_id' => $this->category->id, 'is_active' => true,
        ]);
        $strangersImage = $this->image($other);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.products.update', $product), $this->payload([
                'image_tags' => [$strangersImage->id => ['colour' => 'Indigo', 'texture' => '']],
            ]))
            ->assertSessionHasNoErrors();

        $strangersImage->refresh();

        $this->assertNull(
            $strangersImage->colour,
            'The tag write is scoped to the product being edited, exactly as the delete_images rule is - without it a crafted request retags another product\'s photographs.'
        );
    }

    public function test_a_photo_can_be_tagged_as_it_is_uploaded_on_create(): void
    {
        // The whole point of this pass: an admin creating a product used to have
        // to save it, reopen it, and only then say which shade each photograph
        // showed - because until the save there were no image rows to tag. The
        // form now tags them by their position in images[] instead.
        Storage::fake('public');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'sku' => 'KURTI-NEW',
                'slug' => 'block-print-kurti-new',
                'main_image' => UploadedFile::fake()->image('main.jpg'),
                'images' => [
                    UploadedFile::fake()->image('one.jpg'),
                    UploadedFile::fake()->image('two.jpg'),
                ],
                'new_image_tags' => [
                    'main' => ['colour' => 'Indigo', 'texture' => ''],
                    0 => ['colour' => '', 'texture' => 'Linen'],
                    1 => ['colour' => 'Indigo', 'texture' => 'Linen'],
                ],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $product = Product::where('sku', 'KURTI-NEW')->firstOrFail();
        $images = $product->images()->orderBy('position')->get();

        $this->assertCount(3, $images, 'The main image and both gallery images are saved.');

        $main = $images->firstWhere('is_primary', true);
        $this->assertSame('Indigo', $main->colour);
        $this->assertNull($main->texture);

        $gallery = $images->where('is_primary', false)->values();
        $this->assertNull($gallery[0]->colour, 'The first gallery photo was left on "Any shade".');
        $this->assertSame('Linen', $gallery[0]->texture);
        $this->assertSame('Indigo', $gallery[1]->colour);
        $this->assertSame('Linen', $gallery[1]->texture);
    }

    public function test_an_upload_tag_naming_something_the_product_does_not_offer_is_dropped(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'sku' => 'KURTI-MOSS',
                'slug' => 'block-print-kurti-moss',
                'images' => [UploadedFile::fake()->image('one.jpg')],
                'new_image_tags' => [0 => ['colour' => 'Moss', 'texture' => '']],
            ]))
            ->assertSessionHasNoErrors();

        $image = Product::where('sku', 'KURTI-MOSS')->firstOrFail()->images()->first();

        $this->assertNull(
            $image->colour,
            'Same rule as a saved photo: a tag no picker can produce would hide that photograph from the shop for good.'
        );
    }

    public function test_a_malformed_upload_tag_does_not_break_the_save(): void
    {
        // The key is whatever the request says it is, so nothing downstream may
        // assume there is an array under it, or that it names a file that exists.
        Storage::fake('public');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'sku' => 'KURTI-ODD',
                'slug' => 'block-print-kurti-odd',
                'images' => [UploadedFile::fake()->image('one.jpg')],
                'new_image_tags' => ['99' => ['colour' => 'Indigo', 'texture' => '']],
            ]))
            ->assertSessionHasNoErrors();

        $image = Product::where('sku', 'KURTI-ODD')->firstOrFail()->images()->first();

        $this->assertNull($image->colour, 'A tag whose index matches no uploaded file is simply not applied.');
    }

    public function test_the_create_form_offers_the_tagging_card_before_anything_is_saved(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.products.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('kkNewPhotoTags(', $html);
        $this->assertStringContainsString('new_image_tags[main][colour]', $html);
        $this->assertStringContainsString("'new_image_tags[' + index + '][colour]'", $html);
        $this->assertStringContainsString(
            'hasChoices && (mainPreview || galleryPreviews.length)',
            $html,
            'The card appears as soon as there is a photo to tag and a shade or fabric to tag it with - no save in between.'
        );
    }

    public function test_swapping_the_main_photo_clears_the_tag_chosen_for_the_old_one(): void
    {
        // A gallery row's tag rides on its preview object and leaves with the
        // picture. The main image's cannot - it is posted on its own - so
        // replacing the main photo would otherwise save the shade chosen for
        // the one before it against a different photograph.
        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.products.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("this.\$watch('mainPreview'", $html);
    }

    public function test_the_edit_form_offers_the_same_card_for_newly_added_photos(): void
    {
        $product = $this->product();
        $this->image($product);

        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->getContent();

        // Both: the saved photos keyed by id, and the newly picked ones by index.
        $this->assertStringContainsString('name="image_tags[', $html);
        $this->assertStringContainsString("'new_image_tags[' + index + '][colour]'", $html);
    }

    public function test_the_edit_form_offers_a_dropdown_per_photo(): void
    {
        $product = $this->product();
        $image = $this->image($product);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Photo shades &amp; fabrics', false)
            ->assertSee('image_tags['.$image->id.'][colour]', false)
            ->assertSee('image_tags['.$image->id.'][texture]', false);
    }

    public function test_a_bounced_save_keeps_offering_a_shade_that_was_added_in_it(): void
    {
        // The save fails on something else entirely - a mistyped HSN code - and
        // comes back with the admin's input. The shade they added in that same
        // submission is not in the database yet, so if the dropdowns were built
        // from the database alone there would be no option to be selected, the
        // browser would fall back to "Any shade", and the tag would be gone by
        // the time they fixed the HSN and saved again.
        $product = $this->product();
        $image = $this->image($product);

        $this->actingAs($this->admin(), 'admin')
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), $this->payload([
                'hsn_code' => '12',
                'colours' => [
                    ['name' => 'Indigo', 'hex' => '#2b3a67'],
                    ['name' => 'Rust', 'hex' => '#b7410e'],
                ],
                'textures' => ['Linen', 'Khadi'],
                'image_tags' => [$image->id => ['colour' => 'Rust', 'texture' => 'Khadi']],
            ]))
            ->assertSessionHasErrors('hsn_code')
            ->assertRedirect(route('admin.products.edit', $product));

        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            '<option value="Rust" selected>Rust</option>',
            $html,
            'The shade added in the bounced save must still be offered, and still be the one selected, or fixing the unrelated field silently drops the tag.'
        );
        $this->assertStringContainsString('<option value="Khadi" selected>Khadi</option>', $html);
    }

    public function test_the_dropdowns_stay_away_when_the_product_offers_no_choices(): void
    {
        $product = $this->product();
        $product->forceFill(['attributes' => null])->save();
        $this->image($product);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertDontSee('Photo shades &amp; fabrics', false);
    }
}
