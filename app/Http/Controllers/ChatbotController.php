<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    private const MAX_HISTORY = 10;
    private const MAX_PRODUCTS = 5;
    private const MAX_ORDERS = 3;

    /**
     * Handle an incoming chat message from the storefront widget.
     *
     * POST /chatbot/message
     */
    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'           => ['required', 'string', 'min:1', 'max:300'],
            'history'           => ['nullable', 'array', 'max:' . self::MAX_HISTORY],
            'history.*.role'    => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ]);

        $userMessage = trim($validated['message']);
        $rawHistory  = $validated['history'] ?? [];

        // Build dynamic context from the database
        $products = $this->findRelevantProducts($userMessage);
        $orders   = $this->fetchUserOrders($request);
        $coupons  = $this->fetchActiveCoupons();

        // Build the system prompt and message history
        $systemPrompt = $this->buildSystemPrompt($products, $orders, $coupons);
        $messages     = $this->buildMessageHistory($rawHistory, $userMessage);

        $apiKey = config('services.anthropic.key');

        if (empty($apiKey)) {
            return response()->json([
                'reply'    => 'The shopping assistant is temporarily unavailable. Please contact our support team for help.',
                'products' => [],
            ], 503);
        }

        try {
            $response = Http::timeout(25)
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => config('services.anthropic.model', 'claude-haiku-4-5-20251001'),
                    'max_tokens' => 1024,
                    'system'     => $systemPrompt,
                    'messages'   => $messages,
                ]);

            if ($response->failed()) {
                Log::error('Anthropic API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return response()->json([
                    'reply'    => 'I\'m having a bit of trouble right now. Please try again in a moment, or contact our support team.',
                    'products' => [],
                ]);
            }

            $data  = $response->json();
            $reply = $data['content'][0]['text'] ?? 'Sorry, I didn\'t catch that. Could you rephrase your question?';

            return response()->json([
                'reply'    => $reply,
                'products' => $this->formatProductCards($products),
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Anthropic connection timeout', ['message' => $e->getMessage()]);

            return response()->json([
                'reply'    => 'The assistant is a little slow right now. Please try your question again.',
                'products' => [],
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // System Prompt
    // ─────────────────────────────────────────────────────────────────────────

    private function buildSystemPrompt(array $products, array $orders, array $coupons): string
    {
        $storeName = Setting::get('site_name', config('app.name', 'ForeverKids'));

        $prompt  = "You are the official AI Shopping Assistant for {$storeName}, a premium kids' clothing e-commerce store in India.\n\n";

        $prompt .= "## Your Personality\n";
        $prompt .= "- Warm, friendly, and enthusiastic about children's fashion.\n";
        $prompt .= "- Professional, sales-focused, but never pushy.\n";
        $prompt .= "- Concise: keep responses under 120 words unless a detailed answer is clearly needed.\n";
        $prompt .= "- Never fabricate product details, prices, availability, or policies.\n";
        $prompt .= "- If you're unsure about something, say so honestly and suggest the customer contact support.\n\n";

        $prompt .= "## Store Policies\n";
        $prompt .= "- **Shipping**: Free on orders above ₹499. Standard delivery in 3–7 business days. Express delivery available at checkout for select cities.\n";
        $prompt .= "- **Returns**: 7-day return window from delivery. Items must be unused with original tags. Initiate via Account → Returns on the website.\n";
        $prompt .= "- **Payments**: UPI, credit/debit cards, net banking, digital wallets, and Cash on Delivery (COD up to ₹5,000).\n";
        $prompt .= "- **Size Guide**: Available at /size-guide. We stock sizes from newborn (0–3 months) up to age 15.\n";
        $prompt .= "- **Order Tracking**: Available at Account → Orders, or use the Track Order page with your order number.\n\n";

        if (!empty($coupons)) {
            $prompt .= "## Active Offers & Coupons\n";
            $prompt .= "Share these when customers ask about deals, discounts, or offers:\n";
            foreach ($coupons as $coupon) {
                $prompt .= "- Code **{$coupon['code']}**: {$coupon['description']}\n";
            }
            $prompt .= "\n";
        }

        if (!empty($products)) {
            $prompt .= "## Products Matching This Query\n";
            $prompt .= "Use these in your suggestions. The widget will automatically show product cards.\n";
            foreach ($products as $p) {
                $price = format_price($p['price']);
                $mrp   = format_price($p['mrp']);
                $stock = $p['in_stock'] ? 'In stock' : 'Out of stock';
                $line  = "- {$p['name']} — {$price}";
                if ($p['price'] < $p['mrp']) {
                    $line .= " (was {$mrp})";
                }
                $line .= " | {$stock}";
                if (!empty($p['category'])) {
                    $line .= " | {$p['category']}";
                }
                $line .= " | Link: {$p['url']}";
                $prompt .= $line . "\n";
            }
            $prompt .= "\n";
        }

        if (!empty($orders)) {
            $prompt .= "## Customer's Recent Orders\n";
            foreach ($orders as $o) {
                $line = "- Order #{$o['number']}: {$o['status']} | Total: {$o['total']} | Placed: {$o['date']}";
                if (!empty($o['expected_delivery'])) {
                    $line .= " | Expected delivery: {$o['expected_delivery']}";
                }
                $prompt .= $line . "\n";
            }
            $prompt .= "Direct the customer to Account → Orders for full tracking details.\n\n";
        }

        $prompt .= "## Response Format\n";
        $prompt .= "- Plain text. You may use bullet points starting with '- ' for lists.\n";
        $prompt .= "- Use **bold** (double asterisks) only for important terms like coupon codes or prices.\n";
        $prompt .= "- No markdown headers (# or ##). Keep it conversational.\n";
        $prompt .= "- End with a soft call-to-action where appropriate.\n";

        return $prompt;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Message History Builder
    // ─────────────────────────────────────────────────────────────────────────

    private function buildMessageHistory(array $rawHistory, string $userMessage): array
    {
        // Sanitize and limit history
        $history = collect($rawHistory)
            ->filter(fn ($m) => in_array($m['role'] ?? '', ['user', 'assistant']) && !empty($m['content']))
            ->map(fn ($m) => [
                'role'    => $m['role'],
                'content' => mb_substr(strip_tags((string) $m['content']), 0, 2000),
            ])
            ->slice(-self::MAX_HISTORY)
            ->values()
            ->toArray();

        // Anthropic requires messages to strictly alternate starting with 'user'.
        // Merge consecutive same-role entries to comply.
        $cleaned  = [];
        $lastRole = null;
        foreach ($history as $msg) {
            if ($msg['role'] === $lastRole) {
                $cleaned[count($cleaned) - 1]['content'] .= "\n" . $msg['content'];
            } else {
                $cleaned[]  = $msg;
                $lastRole   = $msg['role'];
            }
        }

        // If history ends with a user turn, the current message would be a duplicate.
        // Remove the stale one — the current message is the canonical user turn.
        if (!empty($cleaned) && end($cleaned)['role'] === 'user') {
            array_pop($cleaned);
        }

        $cleaned[] = ['role' => 'user', 'content' => $userMessage];

        return $cleaned;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Context Builders
    // ─────────────────────────────────────────────────────────────────────────

    private function findRelevantProducts(string $message): array
    {
        $intentKeywords = [
            'dress', 'shirt', 't-shirt', 'tshirt', 'pant', 'jeans', 'skirt', 'frock',
            'jacket', 'sweater', 'hoodie', 'romper', 'onesie', 'uniform', 'shoe', 'shoes',
            'sandal', 'sock', 'socks', 'kurta', 'leggings', 'shorts', 'pajama', 'nightwear',
            'party wear', 'newborn', 'baby', 'toddler', 'infant',
            'show', 'find', 'buy', 'search', 'looking for', 'recommend', 'suggest',
            'product', 'cloth', 'wear', 'outfit', 'clothes', 'clothing', 'apparel',
            'boys', 'girls', 'kids', 'children', 'child', 'size', 'age',
        ];

        $lower = strtolower($message);

        if (!collect($intentKeywords)->some(fn ($kw) => str_contains($lower, $kw))) {
            return [];
        }

        $stopWords = [
            'i', 'want', 'need', 'looking', 'for', 'a', 'an', 'the', 'please',
            'show', 'me', 'find', 'some', 'any', 'do', 'you', 'have', 'is', 'are',
            'what', 'which', 'how', 'much', 'my', 'can', 'could', 'would', 'like',
            'to', 'in', 'on', 'at', 'under', 'over', 'below', 'above', 'get', 'buy',
        ];

        $words       = preg_split('/\s+/', $lower);
        $searchTerms = array_values(array_filter($words, fn ($w) => !in_array($w, $stopWords) && strlen($w) > 2));

        if (empty($searchTerms)) {
            return $this->fetchBestsellers();
        }

        $topTerms = array_slice($searchTerms, 0, 5);

        $products = Product::query()
            ->active()
            ->inStock()
            ->with(['category:id,name', 'brand:id,name', 'primaryImage'])
            ->where(function ($q) use ($topTerms) {
                foreach ($topTerms as $term) {
                    $q->orWhere('name', 'like', "%{$term}%")
                      ->orWhere('short_description', 'like', "%{$term}%")
                      ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$term}%"))
                      ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$term}%"));
                }
            })
            ->orderBy('sales_count', 'desc')
            ->limit(self::MAX_PRODUCTS)
            ->get();

        return $products->map(fn ($p) => $this->mapProduct($p))->toArray();
    }

    private function fetchBestsellers(): array
    {
        return Product::query()
            ->active()
            ->inStock()
            ->with(['category:id,name', 'brand:id,name', 'primaryImage'])
            ->orderBy('sales_count', 'desc')
            ->limit(4)
            ->get()
            ->map(fn ($p) => $this->mapProduct($p))
            ->toArray();
    }

    private function mapProduct(Product $product): array
    {
        return [
            'id'       => $product->id,
            'name'     => $product->name,
            'slug'     => $product->slug,
            'price'    => (float) $product->price,
            'mrp'      => (float) ($product->mrp ?? $product->price),
            'in_stock' => $product->isInStock(),
            'category' => $product->category?->name,
            'brand'    => $product->brand?->name,
            'image'    => $product->primary_image_url,
            'url'      => route('product.show', $product),
        ];
    }

    private function formatProductCards(array $products): array
    {
        return array_map(fn ($p) => [
            'id'          => $p['id'],
            'name'        => $p['name'],
            'price'       => format_price($p['price']),
            'mrp'         => format_price($p['mrp']),
            'has_discount' => $p['price'] < $p['mrp'],
            'url'         => $p['url'],
            'image'       => $p['image'],
            'in_stock'    => $p['in_stock'],
        ], $products);
    }

    private function fetchUserOrders(Request $request): array
    {
        if (!$request->user()) {
            return [];
        }

        return Order::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(self::MAX_ORDERS)
            ->get()
            ->map(fn (Order $o) => [
                'number'            => $o->order_number,
                'status'            => ucfirst(str_replace('_', ' ', $o->status)),
                'total'             => format_price((float) $o->total),
                'date'              => $o->created_at->format('d M Y'),
                'expected_delivery' => $o->expected_delivery_date?->format('d M Y'),
            ])
            ->toArray();
    }

    private function fetchActiveCoupons(): array
    {
        return Coupon::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->whereRaw('(usage_limit IS NULL OR times_used < usage_limit)')
            ->whereNull('applicable_users')
            ->orderBy('value', 'desc')
            ->limit(5)
            ->get()
            ->map(fn (Coupon $c) => [
                'code'        => $c->code,
                'description' => $this->describeCoupon($c),
            ])
            ->toArray();
    }

    private function describeCoupon(Coupon $coupon): string
    {
        $desc = match ($coupon->type) {
            'percentage'  => (int) $coupon->value . '% off',
            'fixed'       => format_price((float) $coupon->value) . ' off',
            'free_shipping' => 'Free shipping',
            'buy_x_get_y' => 'Buy X, Get Y free',
            default       => 'Special discount',
        };

        if (($coupon->min_order_amount ?? 0) > 0) {
            $desc .= ' on orders above ' . format_price((float) $coupon->min_order_amount);
        }

        if ($coupon->max_discount) {
            $desc .= ' (max discount ' . format_price((float) $coupon->max_discount) . ')';
        }

        if ($coupon->expires_at) {
            $desc .= '. Valid till ' . $coupon->expires_at->format('d M Y');
        }

        return $desc;
    }
}
