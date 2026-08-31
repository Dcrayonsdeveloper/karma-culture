<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ChatbotProductClick;
use App\Models\Coupon;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $goesWith = $this->complementaryTo($products);

        // Build the system prompt and message history
        $systemPrompt = $this->buildSystemPrompt($products, $orders, $coupons, $goesWith);
        $messages     = $this->buildMessageHistory($rawHistory, $userMessage);

        if (! AiChatService::isConfigured()) {
            return response()->json([
                'reply'    => 'The shopping assistant is temporarily unavailable. Please contact our support team for help.',
                'products' => [],
            ], 503);
        }

        $conversation = $this->conversationFor($request);
        $startedAt = microtime(true);

        try {
            $result = AiChatService::reply($systemPrompt, $messages);

            if (! $result['ok']) {
                return response()->json([
                    'reply'    => 'I\'m having a bit of trouble right now. Please try again in a moment, or contact our support team.',
                    'products' => [],
                ]);
            }

            $reply = $result['reply'] !== ''
                ? $result['reply']
                : 'Sorry, I didn\'t catch that. Could you rephrase your question?';

            // The model marks intent inline; the customer must never see it.
            $isLead    = str_contains($reply, '[LEAD]');
            $isHandoff = str_contains($reply, '[HANDOFF]');
            $reply     = trim(str_replace(['[LEAD]', '[HANDOFF]'], '', $reply));

            $this->record($conversation, $userMessage, $reply, $products, $startedAt);
            $this->flagIntent($conversation, $isLead, $isHandoff);
            $this->captureLead($conversation, $userMessage, $isLead, $products);

            return response()->json([
                'reply'          => $reply,
                'products'       => $this->formatProductCards($products),
                'conversation_id' => $conversation?->id,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // error, not warning: LOG_LEVEL is error in production, so a warning
            // here is discarded and the timeout leaves no trace to diagnose.
            Log::error('Chatbot connection timeout', ['message' => $e->getMessage()]);

            return response()->json([
                'reply'    => 'The assistant is a little slow right now. Please try your question again.',
                'products' => [],
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Conversation logging
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * One conversation per browser session, so a visitor's whole exchange stays
     * together rather than fragmenting into one row per message.
     */
    private function conversationFor(Request $request): ?ChatbotConversation
    {
        try {
            return ChatbotConversation::firstOrCreate(
                ['session_id' => $request->session()->getId()],
                ['user_id' => $request->user()?->id]
            );
        } catch (\Throwable $e) {
            // Logging must never cost the customer their answer.
            Log::error('Chatbot: could not open conversation', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function record(?ChatbotConversation $conversation, string $question, string $reply, array $products, float $startedAt): void
    {
        if (! $conversation) {
            return;
        }

        try {
            $productIds = array_values(array_map(fn ($p) => $p['id'], $products));

            ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $question,
            ]);

            ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $reply,
                'product_ids' => $productIds ?: null,
                'response_ms' => min(65535, (int) ((microtime(true) - $startedAt) * 1000)),
            ]);

            $conversation->forceFill([
                'message_count' => $conversation->messages()->count(),
                'last_message_at' => now(),
                'user_id' => $conversation->user_id ?? request()->user()?->id,
            ])->save();
        } catch (\Throwable $e) {
            Log::error('Chatbot: could not record messages', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Record what the assistant judged about this conversation, so the
     * dashboard can separate browsing from buying intent and surface the
     * conversations a human still needs to pick up.
     */
    private function flagIntent(?ChatbotConversation $conversation, bool $isLead, bool $isHandoff): void
    {
        if (! $conversation || (! $isLead && ! $isHandoff)) {
            return;
        }

        try {
            $conversation->forceFill([
                'is_lead' => $conversation->is_lead || $isLead,
                'last_intent' => $isHandoff ? 'handoff' : 'lead',
            ])->save();
        } catch (\Throwable $e) {
            Log::error('Chatbot: could not flag intent', ['message' => $e->getMessage()]);
        }
    }
    /**
     * Turn a chat into a followable lead.
     *
     * A contact detail typed into the chat is the customer volunteering it, so
     * it is captured whenever it appears. Without one there is nothing to
     * follow up with, so an intent flag alone does not create a lead.
     */
    private function captureLead(?ChatbotConversation $conversation, string $message, bool $isLead, array $products): void
    {
        if (! $conversation) {
            return;
        }

        preg_match('/[\w.+-]+@[\w-]+\.[\w.]{2,}/', $message, $emailMatch);

        // Indian mobiles, written with or without +91 and with any spacing.
        // Matched on the original text: collapsing the whitespace first glues
        // the number to the next word and loses the boundary.
        $phone = null;
        if (preg_match('/(?:\+?91[\s.-]?)?\b([6-9](?:[\s.-]?\d){9})\b/', $message, $phoneMatch)) {
            $digits = preg_replace('/\D/', '', $phoneMatch[1]);
            $phone = strlen($digits) === 10 ? $digits : null;
        }

        $email = $emailMatch[0] ?? null;

        if (! $email && ! $phone) {
            return;
        }

        try {
            $user = $conversation->user;

            $lead = Lead::updateOrCreate(
                [
                    'platform' => 'website_chat',
                    'platform_id' => $email ?: $phone,
                ],
                [
                    'name' => $user?->full_name,
                    'email' => $email ?: $user?->email,
                    'phone' => $phone ?: $user?->phone,
                    'stage' => $isLead ? 'qualified' : 'new',
                    'notes' => Str::limit('From the shopping assistant. Last message: ' . $message, 480),
                    'tags' => array_values(array_filter(array_map(fn ($p) => $p['name'] ?? null, $products))) ?: null,
                ]
            );

            $conversation->forceFill([
                'lead_id' => $lead->id,
                'is_lead' => true,
            ])->save();
        } catch (\Throwable $e) {
            Log::error('Chatbot: could not capture lead', ['message' => $e->getMessage()]);
        }
    }
    /**
     * A click on a suggested product — the clearest signal the assistant moved
     * someone towards a purchase.
     *
     * POST /chatbot/product-click
     */
    public function productClick(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:chatbot_conversations,id'],
        ]);

        try {
            ChatbotProductClick::create([
                'conversation_id' => $validated['conversation_id'] ?? null,
                'product_id' => $validated['product_id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Chatbot: could not record click', ['message' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // System Prompt
    // ─────────────────────────────────────────────────────────────────────────

    private function buildSystemPrompt(array $products, array $orders, array $coupons, array $goesWith = []): string
    {
        $storeName = Setting::get('site_name', config('app.name', 'Karmaa Kulture'));

        $prompt  = "You are the official AI Shopping Assistant for {$storeName}, a premium fashion e-commerce store in India.\n\n";

        $prompt .= "## Your Personality\n";
        $prompt .= "- Warm, friendly, and enthusiastic about everyday and occasion fashion.\n";
        $prompt .= "- Professional, sales-focused, but never pushy.\n";
        $prompt .= "- Concise: keep responses under 120 words unless a detailed answer is clearly needed.\n";
        $prompt .= "- Never fabricate product details, prices, availability, or policies.\n";
        $prompt .= "- If you're unsure about something, say so honestly and suggest the customer contact support.\n\n";
        $prompt .= "## What We Sell\n";
        $prompt .= "Men's and women's fashion: shirts, t-shirts and polos, kurtas, trousers, tops, jumpsuits, co-ord sets and one pieces. ";
        $prompt .= "This is not a children's store — never describe the range as kids' or babywear, and never quote children's sizes.\n\n";

        $prompt .= "## Store Policies\n";
        $freeShipping = (int) Setting::get('free_shipping_threshold', 999);
        $prompt .= "- **Shipping**: Free on orders above ₹{$freeShipping}. Standard delivery in 3–7 business days. Express delivery available at checkout for select cities.\n";
        $returnDays = (int) Setting::get('return_window_days', 7);
        $prompt .= "- **Returns**: {$returnDays}-day return window from delivery. Items must be unused with original tags. Initiate via Account → Returns on the website.\n";
        $prompt .= "- **Payments**: UPI, credit/debit cards, net banking, digital wallets, and Cash on Delivery (COD up to ₹5,000).\n";
        $prompt .= "- **Size Guide**: Available at /size-guide. Sizes and colours differ per product. Where a product below lists them, those are the real in-stock options — quote them exactly. Where it lists none, say the options are shown on the product page rather than guessing.\n";
        $prompt .= "- **Order Tracking**: Available at Account → Orders, or use the Track Order page with your order number.\n\n";

        // Indian shoppers routinely mix languages in one sentence; answering in
        // the language they used matters more than answering in English.
        $prompt .= "## Fit Advice\n";
        $prompt .= "Some sizes below list measurements in brackets. When a customer gives their own measurement, chest, waist, or the size they usually wear, compare it against those and recommend one size, saying briefly why. ";
        $prompt .= "If a product lists no measurements, point them to the size guide rather than guessing. Never invent a measurement.\n\n";

        $prompt .= "## Language\n";
        $prompt .= "Reply in whatever language the customer writes in — English, Hindi, or Hinglish. ";
        $prompt .= "If they mix, mirror the mix. Keep product names, sizes and coupon codes exactly as written.\n\n";

        $prompt .= "## Selling\n";
        $prompt .= "- Suggest a complete look when it fits: pair a shirt with trousers, a kurta with bottoms. Only ever suggest products listed below — never invent one.\n";
        $prompt .= "- Mention a coupon when the customer hesitates on price or asks about offers. Do not open with a discount.\n";
        $prompt .= "- When someone shows real buying intent but is not ready today, offer to save their email so the team can follow up, and end that reply with [LEAD].\n";
        $prompt .= "- If you cannot answer, or the customer is upset or asking about a specific order problem you have no data for, say a human will help and end that reply with [HANDOFF].\n";
        $prompt .= "- [LEAD] and [HANDOFF] are stripped before the customer sees the message. Use them sparingly and never both at once.\n\n";

        if ($voice = trim((string) Setting::get('chatbot_brand_voice', ''))) {
            $prompt .= "## Brand Voice\n" . $voice . "\n\n";
        }

        // Appended last so it overrides anything above it.
        if ($custom = trim((string) Setting::get('chatbot_extra_instructions', ''))) {
            $prompt .= "## Additional Instructions From The Store Owner\n";
            $prompt .= "These take priority over everything above.\n" . $custom . "\n\n";
        }

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
                if (!empty($p['sizes'])) {
                    // Name the per-size price only where it differs from the base.
                    $sizes = array_map(function ($sz) use ($p) {
                        $label = abs($sz['price'] - $p['price']) < 0.01
                            ? $sz['name']
                            : $sz['name'] . ' ' . format_price($sz['price']);

                        // Measurements let the assistant advise on fit rather
                        // than only listing which sizes exist.
                        return $sz['measurements'] !== ''
                            ? $label . ' (' . $sz['measurements'] . ')'
                            : $label;
                    }, $p['sizes']);
                    $line .= ' | Sizes in stock: ' . implode(', ', $sizes);
                }
                if (!empty($p['colours'])) {
                    $line .= ' | Colours: ' . implode(', ', $p['colours']);
                }
                $line .= " | Link: {$p['url']}";
                $prompt .= $line . "\n";
            }
            $prompt .= "\n";
        }

        if (!empty($goesWith)) {
            $prompt .= "## Goes Well With\n";
            $prompt .= "Products from other categories that complete the look. Suggest one or two only when it helps, and only from this list.\n";
            foreach ($goesWith as $g) {
                $prompt .= "- {$g['name']} — " . format_price($g['price']);
                if (!empty($g['category'])) {
                    $prompt .= " | {$g['category']}";
                }
                $prompt .= " | Link: {$g['url']}\n";
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
            // Matched against the live catalogue: shirts, t-shirts, kurtas,
            // trousers, tops, jumpsuits, co-ord sets and one pieces.
            'shirt', 't-shirt', 'tshirt', 'tee', 'polo', 'henley', 'mandarin', 'oversized',
            'round neck', 'slim fit', 'regular fit', 'kurta', 'trouser', 'pant', 'jeans',
            'denim', 'top', 'jumpsuit', 'co-ord', 'coord', 'one piece', 'dress', 'skirt',
            'shorts', 'jacket', 'sweater', 'hoodie', 'ethnic', 'formal', 'casual',
            'show', 'find', 'buy', 'search', 'looking for', 'recommend', 'suggest',
            'product', 'cloth', 'wear', 'outfit', 'clothes', 'clothing', 'apparel',
            'men', 'mens', 'women', 'womens', 'size', 'colour', 'color',
        ];

        $lower = strtolower($message);

        if (!collect($intentKeywords)->some(fn ($kw) => str_contains($lower, $kw))) {
            return [];
        }

        // Everything a shopper says around the product name. Without the second
        // row a question like "tell me the price, size and colour of X" spends
        // its whole term budget on the question itself and never reaches X.
        $stopWords = [
            'i', 'want', 'need', 'looking', 'for', 'a', 'an', 'the', 'please',
            'show', 'me', 'find', 'some', 'any', 'do', 'you', 'have', 'is', 'are',
            'what', 'which', 'how', 'much', 'my', 'can', 'could', 'would', 'like',
            'to', 'in', 'on', 'at', 'under', 'over', 'below', 'above', 'get', 'buy',
            'tell', 'about', 'available', 'availability', 'this', 'that', 'these',
            'those', 'and', 'or', 'with', 'your', 'our', 'there', 'does', 'did',
            'come', 'comes', 'coming', 'price', 'prices', 'cost', 'size', 'sizes',
            'color', 'colors', 'colour', 'colours', 'product', 'products', 'item',
            'items', 'stock', 'know', 'give', 'name', 'inside', 'from', 'also',
            'hi', 'hey', 'hello', 'thanks', 'thank', 'yes', 'not', 'but', 'all',
        ];

        // A quoted phrase is the customer naming a product outright, so those
        // words go first and are never crowded out by the rest of the sentence.
        $quoted = [];
        if (preg_match_all('/["\x{201C}\x{201D}\']([^"\x{201C}\x{201D}\']{3,60})["\x{201C}\x{201D}\']/u', $lower, $m)) {
            foreach ($m[1] as $phrase) {
                foreach (preg_split('/\s+/', trim($phrase)) as $w) {
                    if (strlen($w) > 2 && !in_array($w, $stopWords, true)) {
                        $quoted[] = $w;
                    }
                }
            }
        }

        $words = preg_split('/\s+/', $lower);
        $rest  = array_filter($words, fn ($w) => !in_array($w, $stopWords, true) && strlen($w) > 2);

        $searchTerms = array_values(array_unique(array_merge($quoted, $rest)));

        if (empty($searchTerms)) {
            return $this->fetchBestsellers();
        }

        $topTerms = array_slice($searchTerms, 0, 8);

        // "under 1500", "below ₹2000", "budget 800" — a price ceiling is a
        // filter, not a search word, and matters more than any keyword.
        $maxPrice = null;
        if (preg_match('/(?:under|below|less than|upto|up to|within|budget(?: of)?|max)\s*(?:rs\.?|inr|₹)?\s*(\d{2,6})/u', $lower, $pm)) {
            $maxPrice = (float) $pm[1];
        }

        $products = Product::query()
            // Match the storefront, which lists on is_active alone. The active()
            // scope also demands status=approved, and no live product has it, so the
            // assistant could never surface a product the customer can actually buy.
            ->where('is_active', true)
            ->inStock()
            ->with(['category:id,name', 'brand:id,name', 'primaryImage', 'variants'])
            ->where(function ($q) use ($topTerms) {
                foreach ($topTerms as $term) {
                    $q->orWhere('name', 'like', "%{$term}%")
                      ->orWhere('short_description', 'like', "%{$term}%")
                      ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$term}%"))
                      ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$term}%"));
                }
            })
            ->when($maxPrice, fn ($q) => $q->where('price', '<=', $maxPrice))
            ->orderBy('sales_count', 'desc')
            ->limit(self::MAX_PRODUCTS)
            ->get();

        return $products->map(fn ($p) => $this->mapProduct($p))->toArray();
    }

    /**
     * Products from *other* categories, so a shirt is paired with trousers
     * rather than with five more shirts. Without this the assistant can only
     * cross-sell by inventing products, which it must never do.
     */
    private function complementaryTo(array $products): array
    {
        if (empty($products)) {
            return [];
        }

        $categoryIds = array_values(array_filter(array_map(fn ($p) => $p['category_id'] ?? null, $products)));
        $productIds  = array_map(fn ($p) => $p['id'], $products);

        return Product::query()
            ->where('is_active', true)
            ->inStock()
            ->with(['category:id,name', 'brand:id,name', 'primaryImage', 'variants'])
            ->whereNotIn('id', $productIds)
            ->when($categoryIds, fn ($q) => $q->whereNotIn('category_id', $categoryIds))
            ->orderBy('sales_count', 'desc')
            ->limit(3)
            ->get()
            ->map(fn ($p) => $this->mapProduct($p))
            ->toArray();
    }

    private function fetchBestsellers(): array
    {
        return Product::query()
            // Match the storefront, which lists on is_active alone. The active()
            // scope also demands status=approved, and no live product has it, so the
            // assistant could never surface a product the customer can actually buy.
            ->where('is_active', true)
            ->inStock()
            ->with(['category:id,name', 'brand:id,name', 'primaryImage', 'variants'])
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
            'category_id' => $product->category_id,
            'brand'    => $product->brand?->name,
            'image'    => $product->primary_image_url,
            'url'      => route('product.show', $product),
            'sizes'    => $this->sizesFor($product),
            'colours'  => $this->coloursFor($product),
        ];
    }

    /**
     * Sizes come from the active variants, each of which carries its own price
     * and stock. Only in-stock sizes are listed: offering a sold-out size is
     * worse than saying nothing.
     */
    private function sizesFor(Product $product): array
    {
        return $product->variants
            ->where('is_active', true)
            ->filter(fn ($v) => trim((string) $v->name) !== '' && $v->stock_quantity > 0)
            ->map(fn ($v) => [
                'name'  => \App\Models\ProductVariant::sizeLabel($v->name),
                'price' => (float) ($v->price ?: $product->price),
                'measurements' => trim((string) data_get($v->attributes, 'measurements', '')),
            ])
            ->unique('name')
            ->values()
            ->all();
    }

    /** Colours are a product-level list set in its own admin section. */
    private function coloursFor(Product $product): array
    {
        return collect(data_get($product->attributes, 'Colours', []))
            ->map(fn ($c) => is_array($c) ? trim((string) ($c['name'] ?? '')) : trim((string) $c))
            ->filter()
            ->unique()
            ->values()
            ->all();
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
