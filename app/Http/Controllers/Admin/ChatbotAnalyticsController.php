<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ChatbotProductClick;
use App\Models\Lead;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChatbotAnalyticsController extends Controller
{
    /** Ranges the dashboard offers, in days. */
    private const RANGES = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 0 => 'All time'];

    public function index(Request $request): View
    {
        $days = (int) $request->input('days', 30);
        if (! array_key_exists($days, self::RANGES)) {
            $days = 30;
        }

        $since = $days > 0 ? now()->subDays($days) : null;

        $conversations = ChatbotConversation::query()
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since));

        $messages = ChatbotMessage::query()
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since));

        $stats = [
            'conversations' => (clone $conversations)->count(),
            'messages' => (clone $messages)->count(),
            'questions' => (clone $messages)->where('role', 'user')->count(),
            'leads' => (clone $conversations)->where('is_lead', true)->count(),
            'handoffs' => (clone $conversations)->where('last_intent', 'handoff')->count(),
            'clicks' => ChatbotProductClick::query()
                ->when($since, fn ($q) => $q->where('created_at', '>=', $since))->count(),
            // Averaged over assistant turns only; user turns have no timing.
            'avg_response_ms' => (int) (clone $messages)->where('role', 'assistant')->avg('response_ms'),
            'signed_in' => (clone $conversations)->whereNotNull('user_id')->count(),
        ];

        // A conversation of one question is a bounce; more is a real exchange.
        $stats['engaged'] = (clone $conversations)->where('message_count', '>', 2)->count();

        $recentQuestions = (clone $messages)
            ->where('role', 'user')
            ->latest('id')
            ->limit(40)
            ->pluck('content');

        // Which products the assistant actually put in front of people.
        $shown = (clone $messages)
            ->where('role', 'assistant')
            ->whereNotNull('product_ids')
            ->pluck('product_ids')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10);

        $shownProducts = Product::whereIn('id', $shown->keys())
            ->pluck('name', 'id')
            ->map(fn ($name, $id) => ['name' => $name, 'count' => $shown[$id] ?? 0])
            ->sortByDesc('count')
            ->values();

        $clickedProducts = ChatbotProductClick::query()
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->select('product_id', DB::raw('COUNT(*) as clicks'))
            ->groupBy('product_id')
            ->orderByDesc('clicks')
            ->limit(10)
            ->with('product:id,name,slug')
            ->get();

        $recentLeads = Lead::where('platform', 'website_chat')
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->latest('id')
            ->limit(10)
            ->get();

        $needsHuman = ChatbotConversation::where('last_intent', 'handoff')
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->with(['user:id,first_name,last_name,email'])
            ->latest('last_message_at')
            ->limit(10)
            ->get();

        return view('admin.chatbot.analytics', [
            'stats' => $stats,
            'ranges' => self::RANGES,
            'days' => $days,
            'topQuestionWords' => $this->commonThemes($recentQuestions),
            'shownProducts' => $shownProducts,
            'clickedProducts' => $clickedProducts,
            'recentLeads' => $recentLeads,
            'needsHuman' => $needsHuman,
            'recentQuestions' => $recentQuestions->take(12),
        ]);
    }

    /**
     * Every customer who has used the assistant, with what they asked and what
     * the assistant put in front of them.
     */
    public function leads(Request $request): View
    {
        $conversations = ChatbotConversation::query()
            ->whereNotNull('user_id')
            ->with(['user', 'lead', 'messages' => fn ($q) => $q->orderBy('id')])
            ->withCount('messages')
            ->latest('last_message_at')
            ->paginate(20);

        // Resolve every product any of these conversations surfaced, in one go.
        $productIds = $conversations->flatMap(
            fn ($c) => $c->messages->pluck('product_ids')->filter()->flatten()
        )->unique()->values();

        $products = Product::whereIn('id', $productIds)
            ->with('variants')
            ->get(['id', 'name', 'slug', 'price', 'attributes'])
            ->keyBy('id');

        $clicks = ChatbotProductClick::whereIn('conversation_id', $conversations->pluck('id'))
            ->get()
            ->groupBy('conversation_id');

        return view('admin.chatbot.leads', compact('conversations', 'products', 'clicks'));
    }

    public function show(ChatbotConversation $conversation): View
    {
        $conversation->load(['messages' => fn ($q) => $q->orderBy('id'), 'user', 'lead']);

        return view('admin.chatbot.conversation', compact('conversation'));
    }

    /**
     * Group questions by the topic words that actually matter to a shop, rather
     * than showing a raw word cloud dominated by "the" and "you".
     */
    private function commonThemes(iterable $questions): array
    {
        $themes = [
            'Size & fit' => ['size', 'sizes', 'fit', 'small', 'medium', 'large', 'xl', 'xxl', 'measurement'],
            'Colour' => ['colour', 'color', 'colours', 'colors', 'red', 'blue', 'black', 'white'],
            'Price & offers' => ['price', 'cost', 'discount', 'coupon', 'offer', 'sale', 'cheap', 'budget'],
            'Availability' => ['stock', 'available', 'availability', 'sold out', 'restock'],
            'Delivery' => ['delivery', 'shipping', 'ship', 'dispatch', 'courier', 'days'],
            'Orders & tracking' => ['order', 'track', 'tracking', 'status', 'parcel'],
            'Returns' => ['return', 'refund', 'exchange', 'replace'],
            'Payment' => ['payment', 'cod', 'upi', 'card', 'pay'],
        ];

        $counts = array_fill_keys(array_keys($themes), 0);

        foreach ($questions as $q) {
            $lower = mb_strtolower((string) $q);
            foreach ($themes as $label => $words) {
                foreach ($words as $w) {
                    if (str_contains($lower, $w)) {
                        $counts[$label]++;
                        break;
                    }
                }
            }
        }

        arsort($counts);

        return array_filter($counts);
    }
}
