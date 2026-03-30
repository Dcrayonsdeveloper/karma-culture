<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $questions = ProductQuestion::whereHas('product', fn($q) => $q->where('seller_id', $request->user()->seller->id))
            ->with(['product:id,name,slug', 'user:id,first_name,last_name'])
            ->latest()
            ->paginate(20);

        return view('seller.questions.index', compact('questions'));
    }

    public function show(Request $request, ProductQuestion $question): View
    {
        $question->load(['product', 'user', 'answers']);

        return view('seller.questions.show', compact('question'));
    }

    public function answer(Request $request, ProductQuestion $question): RedirectResponse
    {
        $validated = $request->validate([
            'answer' => 'required|string|max:1000',
        ]);

        $question->answers()->create([
            'user_id' => $request->user()->id,
            'answer' => $validated['answer'],
        ]);

        $question->update(['is_answered' => true]);

        return back()->with('success', 'Answer submitted');
    }
}
