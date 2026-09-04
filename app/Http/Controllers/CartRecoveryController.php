<?php

namespace App\Http\Controllers;

use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Services\AbandonedCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Opens an abandoned cart from a recovery link.
 *
 * The link carries one thing: a 64-character random token. No email, no name,
 * no cart id, nothing that identifies the customer or that can be guessed from
 * another customer's link.
 *
 * The token is not, on its own, permission to use the basket. Binding a session
 * to a cart hands it real write access - CartController::update() and
 * destroy() authorise a line purely by "does it belong to the cart my session
 * resolves to" - so a link that silently adopted any cart would let whoever
 * held the URL edit someone else's basket. Ownership is therefore re-checked
 * here every time:
 *
 *   - a cart owned by an account only ever opens for that account, and an
 *     anonymous visitor is sent through the login page first;
 *   - a legacy guest cart (nobody owns it) is adopted into the visitor's own
 *     session, or merged into their account cart if they are signed in.
 */
class CartRecoveryController extends Controller
{
    public function __construct(private AbandonedCartService $service) {}

    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $episode = AbandonedCart::where('token', $token)->first();

        // One message for "no such token", "expired" and "archived" on purpose.
        // Telling the difference apart would confirm to somebody guessing tokens
        // that a particular one exists.
        if (! $episode
            || $episode->recovery_status === AbandonedCart::STATUS_ARCHIVED
            || $this->service->recoveryLinkExpiresAt($episode)->isPast()) {
            return redirect()->route('cart.index')
                ->with('error', 'That cart link is no longer valid. Your current cart is shown below.');
        }

        $cart = $episode->cart()->with('items')->first();

        // Same wording as the unknown-token branch on purpose. A different
        // message here would tell somebody guessing tokens that this one is
        // real, just spent.
        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'That cart link is no longer valid. Your current cart is shown below.');
        }

        return $cart->user_id
            ? $this->openOwnedCart($request, $cart)
            : $this->openGuestCart($request, $cart);
    }

    /**
     * A cart that belongs to an account opens only for that account.
     */
    private function openOwnedCart(Request $request, Cart $cart): RedirectResponse
    {
        if (! $request->user()) {
            // Sends them to the login page and remembers this URL, so signing in
            // lands them straight back on their basket. The token stays in the
            // session, never in a query string we hand to anyone else.
            return redirect()->guest(route('login'));
        }

        if ($request->user()->id !== $cart->user_id) {
            // Deliberately vague: a signed-in customer has no business learning
            // that this token belongs to a real cart of somebody else's.
            return redirect()->route('cart.index')
                ->with('error', 'That cart link is not valid for your account.');
        }

        // Nothing to restore - the cart is already theirs and getOrCreateCart()
        // resolves it by user_id. Just show it.
        return redirect()->route('cart.index')
            ->with('success', 'Welcome back! Your saved cart is ready.');
    }

    /**
     * A cart with no owner - only possible for rows created before adding to a
     * cart required an account. Its session id is long dead (sessions last two
     * hours and are regenerated on login), which is precisely why the link
     * needs its own token.
     */
    private function openGuestCart(Request $request, Cart $cart): RedirectResponse
    {
        if ($user = $request->user()) {
            $this->mergeInto($cart, $user->id);

            // The basket has moved to their account cart, so this episode is
            // over. Left open it would sit in the admin list pointing at an
            // empty cart until it expired.
            $this->closeEpisodesFor($cart);

            return redirect()->route('cart.index')
                ->with('success', 'Welcome back! Your saved items have been added to your cart.');
        }

        $sessionId = $request->session()->getId();

        // This browser almost certainly already has a cart row: every page load
        // fires GET /cart/data, which firstOrCreates one on the session id.
        // Leaving it there would give the session two rows, and
        // getOrCreateCart() takes the first - quite possibly the empty one, so
        // the recovered basket would appear to vanish again.
        Cart::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->where('id', '!=', $cart->id)
            ->whereDoesntHave('items')
            ->delete();

        // Adopt it into this browser's session. This does bump the cart's
        // updated_at, which is correct here and nowhere else in this feature:
        // the customer really is active again.
        $cart->update(['session_id' => $sessionId]);

        return redirect()->route('cart.index')
            ->with('success', 'Welcome back! Your saved cart is ready.');
    }

    /**
     * The basket has been taken somewhere else, so the episode is finished.
     *
     * Not marked "recovered" - nothing has been bought yet. Checkout is what
     * decides that, and it will find no open episode for a cart that has been
     * emptied here, which is correct: this row is no longer the basket.
     */
    private function closeEpisodesFor(Cart $cart): void
    {
        AbandonedCart::where('cart_id', $cart->id)
            ->open()
            ->update(['recovery_status' => AbandonedCart::STATUS_EXPIRED]);
    }

    /**
     * Move the recovered lines into the signed-in customer's own cart.
     *
     * Mirrors LoginController::mergeGuestCart(), including the four-column line
     * match - product + variant + size + colour is what `cart_items_line_unique`
     * considers one line, and matching on fewer collapses "Blue / M" and
     * "Red / L" of the same product into one row.
     */
    private function mergeInto(Cart $source, int $userId): void
    {
        $target = Cart::firstOrCreate(['user_id' => $userId], ['session_id' => null]);

        if ($target->id === $source->id) {
            return;
        }

        foreach ($source->items as $item) {
            $existing = $target->items()
                ->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->where('size', $item->size)
                ->where('colour', $item->colour)
                ->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $item->quantity]);
                // The source line MUST go. LoginController gets away without
                // this because it deletes the whole guest cart afterwards; here
                // the cart survives, so a line left behind would be added to the
                // quantity again on every reload of the link.
                $item->delete();
            } else {
                $item->update(['cart_id' => $target->id]);
            }
        }

        $target->recalculate();
    }
}
