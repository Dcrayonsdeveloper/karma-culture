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

        // The anti-spam trap, answered here rather than by the validator.
        //
        // It used to be a rule - 'honeypot' => 'max:0' with no message - so a
        // filled trap printed "The honeypot field must not be greater than 0
        // characters." into the form's error banner. That names the trap and its
        // rule to whoever tripped it, which tells a bot exactly what to leave
        // alone next time, and is meaningless to the shopper who saw it: the box
        // is off-screen and aria-hidden, so the only way a person reaches it is a
        // password manager or an autofill filling it for them.
        //
        // A trap has to answer the way a success answers or it is not a trap, so
        // the submission is dropped and the ordinary thank-you is returned. That
        // is deliberately indistinguishable from a review that was accepted and
        // is awaiting moderation - which is also what protects the rare real
        // shopper whose autofill trips it from being shown an error they cannot
        // act on or understand.
        //
        // The test has to survive being handed something that is not a string,
        // which is why it asks the request rather than casting what it hands
        // back. A bot posts honeypot[]=x as readily as honeypot=x, and
        // (string) $array raises PHP's "Array to string conversion" warning,
        // which HandleExceptions promotes to an ErrorException - so a cast here
        // answered the exact traffic the trap exists to catch with a 500 error
        // page, the one reply that tells a bot its submission was treated as
        // special, and the opposite of the property argued for above.
        // Request::filled() checks is_array() and is_bool() before it casts
        // anything, and treats whitespace as empty, so a blank box and an
        // untouched box both fall through to the real validation while anything
        // actually carrying a value - array included - is dropped.
        if ($request->filled('honeypot')) {
            return back()->with('success', 'Thank you for your review! It will be visible after moderation.');
        }

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
            // Media (Task 10): up to 5 images, up to 2 videos.
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',      // 5 MB
            'videos' => 'nullable|array|max:2',
            'videos.*' => 'mimetypes:video/mp4,video/webm,video/quicktime|max:20480', // 20 MB
        ], [
            // This form passed NO messages at all, so every failure arrived in
            // Laravel's own voice - "The guest name field is required.", "The
            // content field must be at least 20 characters.", and for a rejected
            // upload "The images.0 field must not be greater than 5120
            // kilobytes.", which prints an internal column name, an array index
            // and a unit nobody outside the codebase uses. The banner on the
            // product page renders whatever comes back, so all of it was shown
            // to shoppers verbatim.
            //
            // These are the sentences the boxes themselves would say. The email
            // one is word for word what app.js prints for a malformed address
            // (its typeMismatch branch does not read the label), so that failure
            // now reads identically whichever side catches it. The rest cannot
            // be made identical yet: the inputs on the product page carry no
            // <label> elements, so app.js's labelFor() falls back to the
            // placeholder and says "Share your experience (at least 20
            // characters)… must be at least 20 characters." Naming the field the
            // way a label would is the half that belongs here.
            'guest_name.required' => 'Full name is required.',
            'guest_name.min' => 'Full name must be at least 2 characters.',
            'guest_name.max' => 'Full name must be 100 characters or fewer.',
            'guest_email.required' => 'Email is required.',
            'guest_email.email' => 'Enter a valid email address, like you@example.com.',
            'guest_email.max' => 'Email must be 255 characters or fewer.',
            // The stars are buttons driving a hidden input, and the submit
            // button stays disabled until one is picked, so this is what a post
            // that skipped the page is told.
            'rating.required' => 'Please choose a star rating.',
            'rating.integer' => 'Please choose a rating from 1 to 5 stars.',
            'rating.min' => 'Please choose a rating from 1 to 5 stars.',
            'rating.max' => 'Please choose a rating from 1 to 5 stars.',
            'title.max' => 'Review title must be 255 characters or fewer.',
            'content.required' => 'Your review is required.',
            'content.min' => 'Your review must be at least 20 characters.',
            'content.max' => 'Your review must be 2000 characters or fewer.',
            'images.max' => 'You can add up to 5 photos.',
            'images.*.image' => 'Photos must be image files.',
            'images.*.mimes' => 'Photos must be JPG, PNG or WEBP files.',
            'images.*.max' => 'Each photo must be 5 MB or smaller.',
            'videos.max' => 'You can add up to 2 videos.',
            'videos.*.mimetypes' => 'Videos must be MP4, WEBM or MOV files.',
            'videos.*.max' => 'Each video must be 20 MB or smaller.',
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
