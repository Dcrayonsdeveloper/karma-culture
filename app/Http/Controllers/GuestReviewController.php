<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Rules\ValidationRules as V;
use App\Support\ReviewMediaUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuestReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        // This is the only review form the storefront renders, and the product
        // page posts here whether or not anyone is signed in. It wrote guest_name
        // and guest_email and never user_id, so every review the site has ever
        // taken was filed as a guest - which is why /account/reviews, a hasMany
        // on user_id, told every customer they had never reviewed anything.
        $user = $request->user();

        $validated = $request->validate([
            // Was 'string|max:100'. This name is PUBLISHED under the review on
            // the product page, so a length was the only thing standing between
            // a drive-by "686876988" - or a URL - and the storefront. V::name()
            // is the same PersonName charset every other name box on the site
            // uses, and the form's pattern spells it out for the browser.
            //
            // Both identity boxes go optional - not prohibited - for a signed-in
            // reviewer: the form stops rendering them, but a page rendered logged
            // out and submitted after a login in another tab would still carry
            // them, and rejecting that outright would lose the review.
            'guest_name' => V::name(required: $user === null, max: 100),
            'guest_email' => [$user ? 'nullable' : 'required', 'email', 'max:255'],
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|min:20|max:2000',
            'honeypot' => 'max:0', // anti-spam: must be empty
            // Media (Task 10): up to 5 images, up to 2 videos.
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',      // 5 MB
            'videos' => 'nullable|array|max:2',
            'videos.*' => 'mimetypes:video/mp4,video/webm,video/quicktime|max:20480', // 20 MB
        ]);

        // Who this review belongs to comes from the session, never from the form.
        // The email box is free text, so a signed-in visitor who typed someone
        // else's address would otherwise file the review against that account.
        $email = V::normalizeEmail($user?->email ?? $validated['guest_email']);

        // The account's own name, kept on the row rather than derived on read.
        // users soft-delete, and a soft delete leaves user_id in place while
        // Review::user() resolves to null - so without this a closed account's
        // reviews would all fall back to "Anonymous" on the product page.
        $name = $user ? trim($user->full_name) : $validated['guest_name'];

        // Check for a duplicate review of this product by the same person.
        // Matching on the address alone was the whole guard, and it stops
        // matching the moment a signed-in reviewer no longer types one - so the
        // account is checked too, which also closes the "use another email and
        // review again" hole for anyone signed in.
        $exists = Review::where('product_id', $product->id)
            ->where(function ($q) use ($user, $email) {
                // LOWER on both sides rather than leaning on the column
                // collation: MySQL compares utf8mb4_*_ci case-insensitively but
                // SQLite does not, so the collation alone would behave one way
                // on the server and another in the test suite.
                $q->whereRaw('LOWER(TRIM(guest_email)) = ?', [$email]);

                if ($user) {
                    $q->orWhere('user_id', $user->id);
                }
            })
            ->exists();

        if ($exists) {
            return back()->with('error', 'You have already reviewed this product.');
        }

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            // Kept on both kinds of row so the duplicate check above, the coupon
            // listener's recipient fallback and the published byline all have one
            // column to read whatever happens to the account later.
            'guest_name' => $name,
            'guest_email' => $email,
            'rating' => $validated['rating'],
            // 'title' is optional, so it is absent from the validated set when
            // the field is not submitted at all (API clients, older caches).
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            // Was hardcoded false, because with nobody attached to the row there
            // was no order history to check. A true guest still gets false -
            // orders carry no email column of their own to match against.
            'is_verified_purchase' => (bool) $user?->hasPurchased($product),
            'is_approved' => false,
            'status' => 'pending',
        ]);

        ReviewMediaUploader::attach(
            $review,
            $request->file('images', []),
            $request->file('videos', []),
        );

        return back()->with('success', 'Thank you for your review! It will be visible after moderation.');
    }
}
