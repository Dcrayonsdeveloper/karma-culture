<?php

namespace Tests\Feature\Chatbot;

use App\Http\Controllers\ChatbotController;
use App\Models\ChatbotConversation;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * What the leads page is for: separating people who showed buying intent from
 * people who were only browsing, and keeping a lead's progress once it is won.
 */
class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    private function captureLead(ChatbotConversation $conversation, string $message, bool $isLead, array $products = []): void
    {
        $class = new ReflectionClass(ChatbotController::class);
        $method = $class->getMethod('captureLead');
        $method->setAccessible(true);
        $method->invoke($class->newInstanceWithoutConstructor(), $conversation, $message, $isLead, $products);
    }

    private function conversationFor(User $user): ChatbotConversation
    {
        return ChatbotConversation::create([
            'session_id' => 'sess-' . $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_typed_contact_detail_is_captured(): void
    {
        $user = User::factory()->create(['email' => 'account@example.com']);
        $conversation = $this->conversationFor($user);

        $this->captureLead($conversation, 'reach me on reachme@example.com', true);

        $this->assertDatabaseHas('leads', [
            'platform' => 'website_chat',
            'email' => 'reachme@example.com',
        ]);
    }

    public function test_an_indian_mobile_typed_into_the_chat_is_captured(): void
    {
        $user = User::factory()->create(['phone' => null]);
        $conversation = $this->conversationFor($user);

        $this->captureLead($conversation, 'call me on +91 98765 43210', true);

        $this->assertDatabaseHas('leads', ['phone' => '9876543210']);
    }

    public function test_a_qualified_lead_is_not_demoted_by_a_later_casual_message(): void
    {
        $user = User::factory()->create(['email' => 'buyer@example.com']);
        $conversation = $this->conversationFor($user);

        // They showed intent…
        $this->captureLead($conversation, 'I want to buy this, here is my email', true);
        $this->assertSame('qualified', Lead::first()->stage);

        // …then asked something ordinary.
        $this->captureLead($conversation, 'what are your delivery timings', false);

        $this->assertSame(
            'qualified',
            Lead::first()->fresh()->stage,
            'A lead that already qualified must not drop back to "new" on the next idle question.'
        );
    }

    public function test_browsing_without_intent_does_not_mark_the_conversation_a_lead(): void
    {
        $user = User::factory()->create(['email' => 'browser@example.com']);
        $conversation = $this->conversationFor($user);

        $this->captureLead($conversation, 'do you have this in large', false);

        $this->assertFalse(
            (bool) $conversation->fresh()->is_lead,
            'is_lead is what the dashboard filters on; setting it for every signed-in chat makes it meaningless.'
        );
    }

    public function test_an_existing_name_is_not_wiped_by_a_later_message(): void
    {
        $user = User::factory()->create(['email' => 'named@example.com']);
        $conversation = $this->conversationFor($user);

        $this->captureLead($conversation, 'I am interested', true);
        Lead::first()->update(['name' => 'Priya Sharma']);

        $this->captureLead($conversation, 'one more question', false);

        $this->assertSame(
            'Priya Sharma',
            Lead::first()->fresh()->name,
            'A name already on the lead must survive later messages.'
        );
    }
}
