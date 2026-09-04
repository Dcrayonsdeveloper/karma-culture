<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per abandonment EPISODE, not per cart.
 *
 * The obvious design - a few columns on `carts` - does not work here, for two
 * reasons that only show up once you read how carts behave in this app:
 *
 * 1. A cart row is recycled forever. Checkout empties it in place
 *    (CheckoutController::process) and never deletes it, so the same row is
 *    handed back to the customer for their next basket. `carts.created_at` is
 *    therefore "when this customer first ever had a cart", and a per-cart
 *    reminder counter would accumulate across every basket the account has
 *    ever abandoned. Recovery rate, recovered revenue and "reminders sent"
 *    are only meaningful per episode.
 *
 * 2. `carts.updated_at` IS the abandonment clock - it is what the detection
 *    query reads. Writing bookkeeping onto the cart bumps it, which is the
 *    exact bug the old cron had: it stored `reminder_sent_at` in
 *    `carts.metadata`, the write pushed `updated_at` forward, and the cart
 *    fell back inside the "idle 2h-7d" window, so it was re-mailed every
 *    three days forever. Keeping state off `carts` makes that impossible.
 *
 * The money columns are a deliberate snapshot, not duplication: checkout empties
 * the cart with a mass delete that fires no model events, so `carts.subtotal`
 * and `carts.total` are left holding the figures of the order that emptied them
 * while `cart_items` is gone. The value a cart had when it was abandoned cannot
 * be recovered from anywhere else once it converts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();

            // Snapshot of the owner. Nullable because legacy guest carts exist
            // (adding to a cart has required an account since long before this
            // feature, but rows predating that rule are still in the table).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable();

            // The recovery link's only credential. 64 random chars, unguessable,
            // and never contains customer data - the URL carries this and
            // nothing else.
            $table->string('token', 64)->unique();

            // The cart's real last activity when the episode opened, and the
            // moment it crossed the threshold. Both are frozen here so that
            // later cart activity cannot rewrite the history of this episode.
            $table->timestamp('last_activity_at');
            $table->timestamp('abandoned_at');

            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');

            $table->string('recovery_status', 20)->default('pending');
            $table->unsignedTinyInteger('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            // Set when a send throws, cleared on the next success, so "failed
            // reminder delivery" is visible instead of silently looking unsent.
            $table->string('last_reminder_error', 255)->nullable();
            $table->timestamp('last_contacted_at')->nullable();

            $table->timestamp('recovered_at')->nullable();
            $table->foreignId('recovered_order_id')->nullable()->constrained('orders')->nullOnDelete();

            $table->timestamps();

            $table->index('abandoned_at', 'abandoned_carts_abandoned_at_index');
            $table->index('recovery_status', 'abandoned_carts_recovery_status_index');
            $table->index(['recovery_status', 'abandoned_at'], 'abandoned_carts_status_abandoned_index');
            $table->index('user_id', 'abandoned_carts_user_id_index');
            $table->index('cart_id', 'abandoned_carts_cart_id_index');

            // Detection is idempotent: re-running it for the same cart at the
            // same abandonment instant cannot open a second episode, even if
            // two processes race.
            $table->unique(['cart_id', 'abandoned_at'], 'abandoned_carts_cart_abandoned_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
