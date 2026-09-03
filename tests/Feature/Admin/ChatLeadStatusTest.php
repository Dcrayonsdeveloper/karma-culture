<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\ChatbotConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Chat Leads screen could only show what the assistant inferred - "Buying
 * intent" and "Needs a human" are both written by the bot. Nothing recorded
 * that a person had picked the lead up, parked it, or won it, so a week of
 * follow-up calls left the list looking exactly as it did on day one.
 */
class ChatLeadStatusTest extends TestCase
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

    private function conversation(): ChatbotConversation
    {
        $customer = User::factory()->create([
            'first_name' => 'Asha',
            'last_name'  => 'Rao',
        ]);

        return ChatbotConversation::create([
            'session_id'      => 'sess-lead-status',
            'user_id'         => $customer->id,
            'message_count'   => 4,
            'is_lead'         => true,
            'last_message_at' => now(),
        ]);
    }

    public function test_a_new_conversation_starts_at_new(): void
    {
        $this->assertSame('new', $this->conversation()->fresh()->lead_status);
    }

    public function test_the_leads_screen_offers_the_dropdown_on_every_card(): void
    {
        $conversation = $this->conversation();

        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.chatbot.leads'))
            ->assertOk()
            ->assertSee(route('admin.chatbot.lead-status', $conversation), false)
            ->getContent();

        foreach (ChatbotConversation::LEAD_STATUSES as $value => $label) {
            $this->assertStringContainsString('value="' . $value . '"', $html);
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_an_admin_can_move_a_lead_along(): void
    {
        $conversation = $this->conversation();

        $this->actingAs($this->adminUser, 'admin')
            ->from(route('admin.chatbot.leads'))
            ->put(route('admin.chatbot.lead-status', $conversation), ['lead_status' => 'acquired'])
            ->assertRedirect(route('admin.chatbot.leads'))
            ->assertSessionHas('success');

        $this->assertSame('acquired', $conversation->fresh()->lead_status);
    }

    public function test_a_status_outside_the_list_is_rejected(): void
    {
        $conversation = $this->conversation();

        $this->actingAs($this->adminUser, 'admin')
            ->from(route('admin.chatbot.leads'))
            ->put(route('admin.chatbot.lead-status', $conversation), ['lead_status' => 'won_the_lottery'])
            ->assertSessionHasErrors('lead_status');

        $this->assertSame('new', $conversation->fresh()->lead_status);
    }

    /**
     * The bot's own read of the chat is a separate signal from the team's, and
     * setting one must not quietly rewrite the other.
     */
    public function test_setting_the_status_leaves_the_bots_own_verdict_alone(): void
    {
        $conversation = $this->conversation();

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.chatbot.lead-status', $conversation), ['lead_status' => 'lost']);

        $conversation->refresh();

        $this->assertTrue($conversation->is_lead);
        $this->assertSame('lost', $conversation->lead_status);
    }

    public function test_a_signed_out_visitor_cannot_touch_a_lead(): void
    {
        $conversation = $this->conversation();

        $this->put(route('admin.chatbot.lead-status', $conversation), ['lead_status' => 'acquired'])
            ->assertRedirect();

        $this->assertSame('new', $conversation->fresh()->lead_status);
    }

    /** A value we no longer ship still has to render, rather than blowing up the page. */
    public function test_an_unknown_stored_status_falls_back_to_new(): void
    {
        $conversation = $this->conversation();
        $conversation->forceFill(['lead_status' => 'retired_value'])->save();

        $this->assertSame('new', $conversation->fresh()->leadStatusKey());
        $this->assertSame('New', $conversation->fresh()->leadStatusLabel());

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.chatbot.leads'))
            ->assertOk();
    }
}
