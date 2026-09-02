<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The product form's Media card says "Up to 10 images, 2MB each", but picking
 * twenty in one go attached all twenty, and the admin only found out after the
 * upload when the server rejected the lot.
 *
 * The cap was read off `galleryPreviews`, which is only appended to from a
 * FileReader callback - asynchronous, so it was still empty for the whole
 * synchronous loop and the guard never fired on the first selection. Previews
 * now come from `URL.createObjectURL`, which returns immediately, so the
 * preview list and the FileList that actually gets submitted grow together.
 *
 * The storefront review form was fixed for the same class of bug; see the
 * kkReviewForm component in products/show.blade.php.
 */
class ProductMediaLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Category $category;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Media',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Media Shirts',
            'slug' => 'media-shirts',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Media Test Shirt',
            'slug' => 'media-test-shirt',
            'sku' => 'MTS-001',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function mediaForms(): array
    {
        return [
            'create' => ['create'],
            'edit' => ['edit'],
        ];
    }

    /**
     * @dataProvider mediaForms
     */
    public function test_the_gallery_cap_counts_the_files_on_the_input(string $form): void
    {
        $html = $this->formHtml($form);

        $this->assertStringContainsString(
            'this.galleryFileList.items.length >= GALLERY_MAX',
            $html,
            "The {$form} form's gallery cap is not counting the files attached to the input, so it can be exceeded in one pick."
        );

        $this->assertStringContainsString('const GALLERY_MAX = 10;', $html);
    }

    /**
     * @dataProvider mediaForms
     */
    public function test_the_cap_is_not_read_off_a_list_filled_in_asynchronously(string $form): void
    {
        $html = $this->formHtml($form);

        $this->assertStringNotContainsString(
            'this.galleryPreviews.length >= ',
            $html,
            "The {$form} form is back to counting previews, which are appended after the loop has already attached every file."
        );

        $this->assertStringNotContainsString(
            'this.galleryPreviews.push({ url: e.target.result',
            $html,
            "The {$form} form is back on FileReader for gallery previews, which reintroduces the async gap the cap fell through."
        );
    }

    /**
     * @dataProvider mediaForms
     */
    public function test_previews_are_created_synchronously_so_indexes_stay_in_step(string $form): void
    {
        $html = $this->formHtml($form);

        $this->assertStringContainsString(
            'this.galleryPreviews.push({ url: URL.createObjectURL(file), name: file.name });',
            $html,
            "The {$form} form no longer appends a preview in the same step as the file, so removing a tile can drop the wrong upload."
        );

        $this->assertStringContainsString(
            'URL.revokeObjectURL(this.galleryPreviews[index].url);',
            $html,
            "The {$form} form leaks the object URL when a tile is removed."
        );
    }

    /**
     * @dataProvider mediaForms
     */
    public function test_dropped_files_are_checked_against_the_types_the_server_accepts(string $form): void
    {
        $html = $this->formHtml($form);

        // Drag-and-drop ignores the input's accept list, so a bare `image/*`
        // test let an SVG through to a round trip that was always going to fail.
        $this->assertStringContainsString(
            "const IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];",
            $html
        );

        $this->assertStringNotContainsString(
            "file.type.startsWith('image/')",
            $html,
            "The {$form} form accepts any image/* type again, including ones the server rejects."
        );
    }

    public function test_the_edit_form_caps_videos_the_same_way(): void
    {
        $html = $this->formHtml('edit');

        $this->assertStringContainsString('this.videoFileList.items.length >= VIDEO_MAX', $html);
        $this->assertStringContainsString('const VIDEO_MAX = 5;', $html);
        $this->assertStringNotContainsString("file.type.startsWith('video/')", $html);
    }

    public function test_the_admin_is_told_how_many_files_were_left_out(): void
    {
        $this->assertStringContainsString('left out.', $this->formHtml('create'));
        $this->assertStringContainsString('left out.', $this->formHtml('edit'));
    }

    /**
     * @dataProvider mediaForms
     */
    public function test_rejection_messages_survive_a_blocked_toastr_cdn(string $form): void
    {
        // toastr comes from a CDN and loads after this page's own scripts, so
        // the rest of the admin guards every call. An unguarded one would throw
        // inside the loop and abandon the files not yet examined.
        $html = $this->formHtml($form);

        // Match the per-file rejections specifically. A bare "{ toastr.error("
        // search also hits the A+ partial's own guarded call, which the edit
        // page includes.
        $this->assertStringNotContainsString(
            '{ toastr.error(file.name',
            $html,
            "The {$form} form rejects a file through toastr without the window.toastr guard the rest of the admin uses."
        );

        $this->assertStringContainsString(
            'if (window.toastr) toastr.error(file.name',
            $html,
            "The {$form} form no longer reports rejected files at all."
        );

        $this->assertStringContainsString('if (overCap > 0 && window.toastr)', $html);
    }

    public function test_dropping_a_file_on_an_upload_zone_does_not_navigate_away(): void
    {
        // Without @drop.prevent the browser opens the dropped file and the
        // half-finished edit goes with it. The create form always bound these;
        // the edit form did not.
        $html = $this->formHtml('edit');

        foreach ([
            'handleMainImage($event.dataTransfer.files[0])',
            'handleGalleryFiles($event.dataTransfer.files)',
            'handleVideoFiles($event.dataTransfer.files)',
        ] as $handler) {
            $this->assertStringContainsString(
                '@drop.prevent="'.$handler.'"',
                $html,
                'An edit-form upload zone still lets the browser handle the drop.'
            );
        }

        $this->assertSame(
            3,
            substr_count($html, '@dragover.prevent @dragleave.prevent'),
            'All three edit-form upload zones must cancel the drag, not just some.'
        );
    }

    public function test_the_edit_form_can_show_the_array_level_upload_errors(): void
    {
        // The max:10 / max:5 rules report under `images` and `videos`, not
        // `images.*` - without these blocks the save failed with no message.
        $html = $this->formHtml('edit');

        $this->assertStringContainsString('Up to 10 per save', $html);
        $this->assertStringContainsString('Up to 5 per save', $html);

        $source = file_get_contents(resource_path('views/admin/products/edit.blade.php'));
        foreach (["@error('images')", "@error('videos')"] as $block) {
            $this->assertStringContainsString(
                $block,
                $source,
                "The edit form cannot render the array-level {$block} message."
            );
        }
    }

    public function test_server_rejects_more_than_ten_gallery_images(): void
    {
        Storage::fake('public');

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->productPayload([
                'images' => $this->fakeImages(11),
            ]))
            ->assertSessionHasErrors('images');
    }

    public function test_server_accepts_exactly_ten_gallery_images(): void
    {
        Storage::fake('public');

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->productPayload([
                'images' => $this->fakeImages(10),
            ]))
            ->assertSessionHasNoErrors()
            // A 500 also has no session errors, so pin the redirect too.
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['sku' => 'MEDIA-CAP-1']);
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function fakeImages(int $count): array
    {
        return array_map(
            fn (int $i) => UploadedFile::fake()->image("gallery-{$i}.jpg", 40, 40),
            range(1, $count)
        );
    }

    private function formHtml(string $form): string
    {
        $route = $form === 'create'
            ? route('admin.products.create')
            : route('admin.products.edit', $this->product);

        return $this->actingAs($this->adminUser, 'admin')->get($route)->assertOk()->getContent();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Media Cap Shirt',
            'slug' => 'media-cap-shirt',
            'sku' => 'MEDIA-CAP-1',
            'description' => 'A shirt used to check the media limits.',
            'price' => 1500,
            'stock_quantity' => 3,
            'category_id' => $this->category->id,
            // Both forms render these as <select>, so a real submission always
            // carries the keys. store() reads them with `?:` rather than `??`,
            // so omitting them raises "Undefined array key" and returns a 500.
            'seller_id' => '',
            'brand_id' => '',
        ], $overrides);
    }
}
