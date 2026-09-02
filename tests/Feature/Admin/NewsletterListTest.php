<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin side of the newsletter list.
 *
 * The signup endpoint had been collecting a mobile number since the offer
 * popup made one mandatory, and the popup tells the shopper their offers
 * arrive over WhatsApp - but the number was written to the database and then
 * shown nowhere: not in the table, not in the search, not in the CSV. The list
 * also filtered on a `source` the page never offered a control for, and the
 * Export button ignored every filter except the status tab, so searching for
 * one subscriber and exporting downloaded all of them.
 */
class NewsletterListTest extends TestCase
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

    private function subscriber(array $attributes = []): NewsletterSubscriber
    {
        return NewsletterSubscriber::create(array_merge([
            'email'         => 'asha@example.com',
            'name'          => 'Asha Menon',
            'phone'         => '9876543210',
            'source'        => 'offer_popup',
            'is_active'     => true,
            'subscribed_at' => now(),
        ], $attributes));
    }

    private function index(array $filters = [])
    {
        return $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.newsletter.index', $filters));
    }

    private function export(array $filters = [])
    {
        return $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.newsletter.export', $filters));
    }

    // ---- the collected mobile number -------------------------------------

    public function test_the_list_shows_the_mobile_number_it_collected(): void
    {
        $this->subscriber();

        $this->index()->assertOk()
            ->assertSee('Mobile')
            ->assertSee('9876543210');
    }

    public function test_the_mobile_column_survives_a_subscriber_who_has_no_number(): void
    {
        // The exit-intent popup treats the number as optional, so the column has
        // to render for a row that has none rather than shifting the table.
        $this->subscriber(['phone' => null, 'source' => 'exit_intent']);

        $this->index()->assertOk()->assertSee('Mobile');
    }

    public function test_search_matches_a_mobile_number(): void
    {
        $this->subscriber();
        $this->subscriber(['email' => 'ravi@example.com', 'name' => 'Ravi Kumar', 'phone' => '9000000001']);

        $this->index(['search' => '9876543210'])
            ->assertOk()
            ->assertSee('asha@example.com')
            ->assertDontSee('ravi@example.com');
    }

    public function test_search_still_matches_an_email_and_a_name(): void
    {
        $this->subscriber();
        $this->subscriber(['email' => 'ravi@example.com', 'name' => 'Ravi Kumar', 'phone' => '9000000001']);

        $this->index(['search' => 'ravi@'])->assertOk()
            ->assertSee('ravi@example.com')->assertDontSee('asha@example.com');

        $this->index(['search' => 'Asha'])->assertOk()
            ->assertSee('asha@example.com')->assertDontSee('ravi@example.com');
    }

    public function test_a_like_wildcard_in_the_search_is_a_literal(): void
    {
        // '%' typed into the box used to reach LIKE unescaped and match every
        // row, so a search that should find nothing returned the whole list.
        $this->subscriber();

        $this->index(['search' => '%'])
            ->assertOk()
            ->assertSee('No subscribers found')
            ->assertDontSee('asha@example.com');
    }

    // ---- the source filter -----------------------------------------------

    public function test_the_source_filter_is_offered_once_there_is_more_than_one(): void
    {
        $this->subscriber();
        $this->subscriber(['email' => 'ravi@example.com', 'source' => 'exit_intent']);

        $this->index()->assertOk()
            ->assertSee('All sources')
            ->assertSee('name="source"', false);
    }

    public function test_the_source_filter_narrows_the_list(): void
    {
        $this->subscriber();
        $this->subscriber(['email' => 'ravi@example.com', 'source' => 'exit_intent']);

        $this->index(['source' => 'exit_intent'])
            ->assertOk()
            ->assertSee('ravi@example.com')
            ->assertDontSee('asha@example.com');
    }

    public function test_the_source_badge_is_readable(): void
    {
        $this->subscriber();

        $this->index()->assertOk()
            ->assertSee('Offer Popup')
            ->assertDontSee('Offer_popup');
    }

    // ---- the export ------------------------------------------------------

    public function test_the_export_carries_the_mobile_number(): void
    {
        $this->subscriber();

        $csv = $this->export()->assertOk()->getContent();

        $this->assertStringContainsString('Email,Name,Phone,Source,Status,Subscribed At', $csv);
        $this->assertStringContainsString('9876543210', $csv);
    }

    public function test_the_export_honours_the_search(): void
    {
        $this->subscriber();
        $this->subscriber(['email' => 'ravi@example.com', 'name' => 'Ravi Kumar', 'phone' => '9000000001']);

        $csv = $this->export(['search' => 'ravi@'])->assertOk()->getContent();

        $this->assertStringContainsString('ravi@example.com', $csv);
        $this->assertStringNotContainsString('asha@example.com', $csv);
    }

    public function test_the_export_honours_the_source(): void
    {
        $this->subscriber();
        $this->subscriber(['email' => 'ravi@example.com', 'source' => 'exit_intent']);

        $csv = $this->export(['source' => 'exit_intent'])->assertOk()->getContent();

        $this->assertStringContainsString('ravi@example.com', $csv);
        $this->assertStringNotContainsString('asha@example.com', $csv);
    }

    public function test_the_export_honours_the_status_tab(): void
    {
        $this->subscriber();
        $this->subscriber(['email' => 'ravi@example.com', 'is_active' => false]);

        $csv = $this->export(['status' => 'active'])->assertOk()->getContent();

        $this->assertStringContainsString('asha@example.com', $csv);
        $this->assertStringNotContainsString('ravi@example.com', $csv);
    }

    public function test_the_export_button_passes_the_filters_on_screen(): void
    {
        $this->subscriber();
        $this->subscriber(['email' => 'ravi@example.com', 'source' => 'exit_intent']);

        // The button used to append the query string only when a status tab was
        // open, so a search-only or source-only view exported everything.
        $this->index(['search' => 'ravi@', 'source' => 'exit_intent'])
            ->assertOk()
            ->assertSee(route('admin.newsletter.export').'?search=ravi%40&amp;source=exit_intent', false);
    }

    public function test_a_formula_in_a_name_stays_text_in_the_export(): void
    {
        $this->subscriber(['name' => '=HYPERLINK("http://evil","click")']);

        $csv = $this->export()->assertOk()->getContent();

        $this->assertStringContainsString("\"\t=HYPERLINK(", $csv);
    }

    // ---- the actions the page offers -------------------------------------

    public function test_a_subscriber_can_be_unsubscribed_and_resubscribed(): void
    {
        $subscriber = $this->subscriber();

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.newsletter.toggle-status', $subscriber))
            ->assertRedirect();

        $subscriber->refresh();
        $this->assertFalse($subscriber->is_active);
        $this->assertNotNull($subscriber->unsubscribed_at);

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.newsletter.toggle-status', $subscriber))
            ->assertRedirect();

        $subscriber->refresh();
        $this->assertTrue($subscriber->is_active);
        $this->assertNull($subscriber->unsubscribed_at);
    }

    public function test_a_bulk_deactivate_applies_to_every_ticked_row(): void
    {
        $one = $this->subscriber();
        $two = $this->subscriber(['email' => 'ravi@example.com']);

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.newsletter.bulk-action'), [
                'action' => 'deactivate',
                'ids'    => [$one->id, $two->id],
            ])
            ->assertRedirect();

        $this->assertFalse($one->refresh()->is_active);
        $this->assertFalse($two->refresh()->is_active);
    }

    public function test_an_unknown_status_filter_is_rejected_rather_than_ignored(): void
    {
        $this->subscriber();

        $this->index(['status' => 'nonsense'])->assertSessionHasErrors('status');
    }
}
