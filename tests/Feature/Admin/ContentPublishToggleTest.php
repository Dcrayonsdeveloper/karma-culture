<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\BlogPost;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Taking a page or a post down used to mean opening the editor and unticking a
 * checkbox: the list offered View, Edit and Delete but nothing that turned a
 * published row back into a draft.
 */
class ContentPublishToggleTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);

        Admin::create([
            'user_id'   => $this->adminUser->id,
            'role'      => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_pages_list_offers_the_toggle_on_every_tab(): void
    {
        $page = Page::create([
            'title'        => 'Shipping Policy',
            'slug'         => 'shipping-policy',
            'is_published' => true,
            'published_at' => now(),
        ]);

        foreach ([[], ['status' => 'published']] as $filters) {
            $this->actingAs($this->adminUser, 'admin')
                ->get(route('admin.pages.index', $filters))
                ->assertOk()
                ->assertSee(route('admin.pages.toggle-status', $page), false)
                ->assertSee('Move to drafts');
        }
    }

    public function test_a_published_page_can_be_moved_to_drafts_and_back(): void
    {
        $page = Page::create([
            'title'        => 'Shipping Policy',
            'slug'         => 'shipping-policy',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.pages.toggle-status', $page))
            ->assertRedirect();

        $page->refresh();
        $this->assertFalse($page->is_published);
        // Cleared, so the list's Published column cannot show a date next to a
        // row badged Draft.
        $this->assertNull($page->published_at);

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.pages.toggle-status', $page))
            ->assertRedirect();

        $page->refresh();
        $this->assertTrue($page->is_published);
        $this->assertNotNull($page->published_at);
    }

    public function test_the_draft_tab_lists_a_page_taken_down_from_the_editor(): void
    {
        $page = Page::create([
            'title'        => 'Shipping Policy',
            'slug'         => 'shipping-policy',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.pages.update', $page), [
                'title'        => 'Shipping Policy',
                'slug'         => 'shipping-policy',
                'is_published' => '0',
            ])
            ->assertRedirect(route('admin.pages.index'));

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.pages.index', ['status' => 'draft']))
            ->assertOk()
            ->assertSee('Shipping Policy');
    }

    public function test_a_published_post_can_be_moved_to_drafts_and_back(): void
    {
        $post = BlogPost::create([
            'title'        => 'Hello Blog',
            'slug'         => 'hello-blog',
            'content'      => 'Body',
            'author_id'    => $this->adminUser->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.blog-posts.index'))
            ->assertOk()
            ->assertSee('Unpublish');

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.blog-posts.toggle-status', $post))
            ->assertRedirect();

        $post->refresh();
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.blog-posts.toggle-status', $post))
            ->assertRedirect();

        $this->assertTrue($post->refresh()->is_published);
    }

    public function test_a_guest_cannot_toggle_a_page(): void
    {
        $page = Page::create([
            'title'        => 'Shipping Policy',
            'slug'         => 'shipping-policy',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->put(route('admin.pages.toggle-status', $page))->assertRedirect();

        $this->assertTrue($page->refresh()->is_published);
    }
}
