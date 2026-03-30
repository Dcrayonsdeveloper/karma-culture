<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $promotions = Promotion::where('seller_id', $request->user()->seller->id)
            ->latest()
            ->paginate(20);

        return view('seller.promotions.index', compact('promotions'));
    }

    public function create(): View
    {
        return view('seller.promotions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'product_ids' => 'nullable|array',
        ]);

        $validated['seller_id'] = $request->user()->seller->id;

        Promotion::create($validated);

        return redirect()->route('seller.promotions.index')->with('success', 'Promotion created');
    }

    public function edit(Request $request, Promotion $promotion): View
    {
        if ($promotion->seller_id !== $request->user()->seller->id) {
            abort(403);
        }

        return view('seller.promotions.edit', compact('promotion'));
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        if ($promotion->seller_id !== $request->user()->seller->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'product_ids' => 'nullable|array',
        ]);

        $promotion->update($validated);

        return redirect()->route('seller.promotions.index')->with('success', 'Promotion updated');
    }

    public function destroy(Request $request, Promotion $promotion): RedirectResponse
    {
        if ($promotion->seller_id !== $request->user()->seller->id) {
            abort(403);
        }

        $promotion->delete();

        return redirect()->route('seller.promotions.index')->with('success', 'Promotion deleted');
    }
}
