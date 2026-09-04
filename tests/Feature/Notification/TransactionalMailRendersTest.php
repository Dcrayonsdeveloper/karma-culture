<?php

namespace Tests\Feature\Notification;

use App\Mail\OrderConfirmation;
use App\Mail\OrderDelivered as OrderDeliveredMail;
use App\Mail\OrderShipped as OrderShippedMail;
use App\Mail\RefundProcessed as RefundProcessedMail;
use App\Mail\ReturnApproved;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Every transactional template, actually rendered.
 *
 * Mail::fake() records a mailable without ever building it, so the whole suite
 * could be green on templates that throw the moment something tries to send
 * them - and until NotificationService started sending rather than queueing,
 * nothing ever did. These five spent months being handed to a queue with no
 * worker: not one of them has been rendered for delivery in production.
 *
 * render() is what the mailer calls, so a missing variable, a null relation or
 * a renamed route surfaces here rather than in a customer's silence.
 */
class TransactionalMailRendersTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_order_confirmation_renders(): void
    {
        $html = (new OrderConfirmation($this->order()))->render();

        $this->assertStringContainsString('KK-RENDER-1', $html);
        $this->assertStringContainsString('Cotton Kurta', $html);
    }

    public function test_the_shipped_mail_renders(): void
    {
        $html = (new OrderShippedMail($this->order(), 'TRK-99001'))->render();

        $this->assertStringContainsString('TRK-99001', $html);
    }

    public function test_the_delivered_mail_renders(): void
    {
        $html = (new OrderDeliveredMail($this->order()))->render();

        $this->assertStringContainsString('KK-RENDER-1', $html);
    }

    public function test_the_return_approved_mail_renders(): void
    {
        $html = (new ReturnApproved($this->orderReturn()))->render();

        $this->assertStringContainsString('RET-RENDER-1', $html);
    }

    public function test_the_refund_processed_mail_renders(): void
    {
        $html = (new RefundProcessedMail($this->orderReturn(), 1200))->render();

        $this->assertStringContainsString('RET-RENDER-1', $html);
    }

    /**
     * The links point at the configured site, not at whatever host the request
     * that triggered the mail happened to carry.
     *
     * This is what the queue used to give these templates for free: a worker
     * renders with no request, so route() fell back to APP_URL. Sending inside
     * the request would hand that host to the caller instead, and this app
     * trusts X-Forwarded-Host from anyone - so the confirmation for an order
     * placed with a spoofed header would carry a working "View Your Order"
     * button pointing at someone else's domain.
     *
     * Rendering here happens under the test request, whose host is localhost.
     * Asserting the configured host instead is what pins the behaviour: remove
     * the forceRootUrl in NotificationService and this fails.
     */
    public function test_transactional_links_use_the_configured_site_not_the_request_host(): void
    {
        config(['app.url' => 'https://shop.example.test']);

        $order = $this->order();

        app(NotificationService::class)->notifyByEmail($order->user, new OrderConfirmation($order));

        $body = $this->lastSentHtml();

        $this->assertStringContainsString('https://shop.example.test/account/orders/', $body);
        $this->assertStringNotContainsString('localhost', $body);
    }

    /** And the request's own URL generation is left as it was afterwards. */
    public function test_the_root_url_is_restored_after_sending(): void
    {
        config(['app.url' => 'https://shop.example.test']);

        $order = $this->order();
        $before = url('/cart');

        app(NotificationService::class)->notifyByEmail($order->user, new OrderConfirmation($order));

        $this->assertSame($before, url('/cart'), 'The mail send left the URL generator pinned.');
    }

    private function lastSentHtml(): string
    {
        $messages = Mail::mailer()->getSymfonyTransport()->messages();

        $this->assertNotEmpty($messages, 'Nothing was sent - the mailable never rendered.');

        $email = $messages->last()->getOriginalMessage();

        $this->assertInstanceOf(\Symfony\Component\Mime\Email::class, $email);

        return (string) $email->getHtmlBody();
    }

    private function customer(): User
    {
        return User::factory()->create([
            'first_name' => 'Asha',
            'last_name' => 'Menon',
            'role' => 'customer',
        ]);
    }

    private function product(): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'kurtas'],
            ['name' => 'Kurtas', 'is_active' => true]
        );

        return Product::create([
            'name' => 'Cotton Kurta',
            'slug' => 'cotton-kurta-'.uniqid(),
            'sku' => 'CK-'.uniqid(),
            'price' => 600,
            'mrp' => 700,
            'cost_price' => 300,
            'stock_quantity' => 20,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    private function order(?User $customer = null): Order
    {
        $order = Order::create([
            'order_number' => 'KK-RENDER-1',
            'user_id' => ($customer ?? $this->customer())->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 1200,
            'discount' => 100,
            'tax' => 60,
            'shipping_cost' => 40,
            'total' => 1200,
            'paid_amount' => 1200,
            'shipping_address_snapshot' => [
                'name' => 'Asha Menon',
                'address_line_1' => '12 Marina Road',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'postal_code' => '600001',
            ],
            'source' => 'web',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product()->id,
            'sku' => 'CK-ITEM',
            'product_name' => 'Cotton Kurta',
            'variant_name' => 'Indigo / M',
            'quantity' => 2,
            'mrp' => 700,
            'price' => 600,
            'tax' => 60,
            'discount' => 100,
            'total' => 1200,
        ]);

        return $order->fresh(['items', 'user']);
    }

    private function orderReturn(): OrderReturn
    {
        $order = $this->order();

        return OrderReturn::create([
            'return_number' => 'RET-RENDER-1',
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'type' => 'return',
            'status' => 'approved',
            'reason' => 'Wrong size',
            'refund_amount' => 1200,
            'refund_method' => 'original',
            'approved_at' => now(),
        ])->fresh(['order', 'user', 'items']);
    }
}
