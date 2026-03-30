<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuestReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'guest_name' => 'required|string|max:100',
            'guest_email' => 'required|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|min:20|max:2000',
            'honeypot' => 'max:0', // anti-spam: must be empty
        ]);

        // Check for duplicate guest review on same product
        $exists = Review::where('product_id', $product->id)
            ->where('guest_email', $validated['guest_email'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'You have already reviewed this product.');
        }

        Review::create([
            'product_id' => $product->id,
            'guest_name' => $validated['guest_name'],
            'guest_email' => $validated['guest_email'],
            'rating' => $validated['rating'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_verified_purchase' => false,
            'is_approved' => false,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Thank you for your review! It will be visible after moderation.');
    }
}
