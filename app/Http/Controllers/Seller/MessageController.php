<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = Conversation::where('seller_id', $request->user()->seller->id)
            ->with(['user:id,first_name,last_name', 'latestMessage'])
            ->latest()
            ->paginate(20);

        return view('seller.messages.index', compact('conversations'));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        if ($conversation->seller_id !== $request->user()->seller->id) {
            abort(403);
        }

        $conversation->load(['messages.sender', 'user']);
        $conversation->messages()->where('sender_type', '!=', 'seller')->update(['read_at' => now()]);

        return view('seller.messages.show', compact('conversation'));
    }

    public function reply(Request $request, Conversation $conversation): RedirectResponse
    {
        if ($conversation->seller_id !== $request->user()->seller->id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'sender_type' => 'seller',
            'content' => $validated['message'],
        ]);

        return back()->with('success', 'Message sent');
    }
}
