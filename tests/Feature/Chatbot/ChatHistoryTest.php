<?php

namespace Tests\Feature\Chatbot;

use App\Models\Category;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The widget is rebuilt from scratch on every page load with `messages: []`
 * and keeps nothing client-side, so navigating anywhere looked to the customer
 * like the assistant had forgotten the conversation — even though every turn
 * was already stored. These cover the endpoint that gives it back.
 */
class ChatHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'customer']);
    }

    private function conversationWithTurns(array $overrides = []): ChatbotConversation
    {
        return ChatbotConversation::create(array_merge([
            'session_id' => 'sess-'.uniqid(),
            'user_id' => $this->user->id,
        ], $overrides));
    }

    public function test_history_requires_authentication(): void
    {
        $this->get('/chatbot/history')->assertRedirect('/login');
    }

    public function test_history_is_empty_for_a_customer_who_has_never_chatted(): void
    {
        $response = $this->actingAs($this->user)->get('/chatbot/history');

        $response->assertOk();
        $response->assertJson(['conversation_id' => null, 'messages' => []]);
    }

    public function test_history_returns_saved_turns_in_order(): void
    {
        $conversation = $this->conversationWithTurns();

        ChatbotMessage::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => 'Do you have linen shirts?']);
        ChatbotMessage::create(['conversation_id' => $conversation->id, 'role' => 'assistant', 'content' => 'Yes, we do.']);

        $response = $this->actingAs($this->user)->get('/chatbot/history');

        $response->assertOk();
        $response->assertJsonPath('conversation_id', $conversation->id);
        $response->assertJsonPath('messages.0.role', 'user');
        $response->assertJsonPath('messages.0.content', 'Do you have linen shirts?');
        $response->assertJsonPath('messages.1.role', 'assistant');
        $response->assertJsonPath('messages.1.content', 'Yes, we do.');
    }

    public function test_saved_product_ids_are_rehydrated_into_cards(): void
    {
        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Linen Shirt', 'slug' => 'linen-shirt', 'sku' => 'LS-1',
            'price' => 1499, 'mrp' => 1999, 'cost_price' => 600, 'stock_quantity' => 5,
            'category_id' => $category->id, 'status' => 'approved', 'is_active' => true,
        ]);

        $conversation = $this->conversationWithTurns();
        ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Here is one.',
            'product_ids' => [$product->id],
        ]);

        $response = $this->actingAs($this->user)->get('/chatbot/history');

        $response->assertOk();
        $response->assertJsonPath('messages.0.products.0.id', $product->id);
        $response->assertJsonPath('messages.0.products.0.name', 'Linen Shirt');
        // has_discount drives the struck-through MRP in both surfaces.
        $response->assertJsonPath('messages.0.products.0.has_discount', true);
        $this->assertNotEmpty($response->json('messages.0.products.0.url'));
    }

    public function test_a_product_deactivated_since_the_reply_is_dropped_not_rendered_broken(): void
    {
        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts-2', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Retired Shirt', 'slug' => 'retired-shirt', 'sku' => 'RS-1',
            'price' => 999, 'mrp' => 999, 'cost_price' => 400, 'stock_quantity' => 0,
            'category_id' => $category->id, 'status' => 'approved', 'is_active' => false,
        ]);

        $conversation = $this->conversationWithTurns();
        ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Here is one.',
            'product_ids' => [$product->id],
        ]);

        $response = $this->actingAs($this->user)->get('/chatbot/history');

        $response->assertOk();
        $response->assertJsonPath('messages.0.products', []);
    }

    public function test_one_customer_never_sees_another_customers_conversation(): void
    {
        $other = User::factory()->create(['role' => 'customer']);
        $theirs = ChatbotConversation::create(['session_id' => 'sess-other', 'user_id' => $other->id]);
        ChatbotMessage::create(['conversation_id' => $theirs->id, 'role' => 'user', 'content' => 'private question']);

        $response = $this->actingAs($this->user)->get('/chatbot/history');

        $response->assertOk();
        $response->assertJsonPath('messages', []);
        $response->assertDontSee('private question');
    }

    public function test_reading_history_does_not_create_an_empty_conversation_row(): void
    {
        $this->actingAs($this->user)->get('/chatbot/history')->assertOk();

        $this->assertDatabaseCount('chatbot_conversations', 0);
    }

    public function test_the_full_page_chat_loads_for_a_signed_in_customer(): void
    {
        $this->actingAs($this->user)->get('/chat')->assertOk()->assertSee('Shopping Assistant');
    }

    public function test_the_full_page_chat_requires_authentication(): void
    {
        $this->get('/chat')->assertRedirect('/login');
    }

    /**
     * The page originally wrapped its script in @push('scripts'), but
     * components/layouts/app.blade.php yields no stacks — so the script was
     * silently dropped, chatPage() was never defined and the page rendered as
     * a dead shell stuck on its loading spinner. Asserting the page "loads"
     * cannot catch that; asserting the component reaches the HTML can.
     */
    public function test_the_chat_page_actually_ships_its_alpine_component(): void
    {
        $html = $this->actingAs($this->user)->get('/chat')->getContent();

        $this->assertStringContainsString('x-data="chatPage()"', $html);
        $this->assertStringContainsString('function chatPage()', $html,
            'The chatPage() definition never reached the page — a pushed script with no '
            .'matching @stack is discarded, leaving the page inert.');
        // The endpoints it calls must be real, resolved URLs.
        $this->assertStringContainsString(route('chatbot.history'), $html);
        $this->assertStringContainsString(route('chatbot.message'), $html);
    }

    public function test_the_floating_widget_is_not_rendered_on_the_full_page_chat(): void
    {
        $html = $this->actingAs($this->user)->get('/chat')->getContent();

        // The floating panel must not sit on top of the page it links to.
        $this->assertStringNotContainsString('chatbot-widget-root', $html);
    }
}
