<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add PIN column to staff for POS login
        Schema::table('staff', function (Blueprint $table) {
            $table->string('pin', 255)->nullable()->after('role'); // bcrypt hashed
        });

        // Add register_id to staff_shifts for terminal tracking
        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->foreignId('register_id')->nullable()->after('store_id')
                  ->constrained('pos_registers')->nullOnDelete();
            $table->text('notes')->nullable()->after('register_summary');
        });

        // Held bills (parked carts)
        Schema::create('pos_held_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('register_id')->constrained('pos_registers')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('items'); // cart items snapshot
            $table->json('discount_data')->nullable(); // applied discounts
            $table->string('note', 255)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'created_at']);
            $table->index(['store_id', 'register_id']);
        });

        // Cash movements (in/out/refund per shift)
        Schema::create('pos_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('staff_shifts')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['sale', 'refund', 'cash_in', 'cash_out'])->default('sale');
            $table->decimal('amount', 12, 2);
            $table->string('reference_type', 50)->nullable(); // pos_sale, pos_return, manual
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['shift_id', 'type']);
        });

        // Audit log for all sensitive POS actions
        Schema::create('pos_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('action', 50); // sale_created, sale_voided, return_processed, etc.
            $table->string('entity_type', 50)->nullable(); // pos_sale, pos_return, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('authorized_by')->nullable(); // manager who approved
            $table->string('terminal_id', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['staff_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        // Add tax detail columns to pos_sale_items
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->string('hsn_code', 20)->nullable()->after('product_name');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('hsn_code');
            $table->decimal('cgst', 10, 2)->default(0)->after('tax');
            $table->decimal('sgst', 10, 2)->default(0)->after('cgst');
            $table->decimal('igst', 10, 2)->default(0)->after('sgst');
            $table->string('discount_reason', 255)->nullable()->after('discount');
        });

        // Add exchange support to pos_returns
        Schema::table('pos_returns', function (Blueprint $table) {
            $table->enum('type', ['return', 'exchange'])->default('return')->after('return_number');
            $table->unsignedBigInteger('exchange_sale_id')->nullable()->after('credit_note_id');
            $table->unsignedBigInteger('authorized_by')->nullable()->after('status');

            $table->foreign('exchange_sale_id')->references('id')->on('pos_sales')->nullOnDelete();
        });

        // Return line items (currently missing)
        Schema::create('pos_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_return_id')->constrained('pos_returns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_name');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('price', 12, 2);
            $table->decimal('refund_amount', 12, 2);
            $table->string('reason', 100)->nullable();
            $table->enum('condition', ['unused_with_tags', 'used', 'defective'])->default('unused_with_tags');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_return_items');
        Schema::dropIfExists('pos_audit_log');
        Schema::dropIfExists('pos_cash_movements');
        Schema::dropIfExists('pos_held_bills');

        Schema::table('pos_returns', function (Blueprint $table) {
            $table->dropForeign(['exchange_sale_id']);
            $table->dropColumn(['type', 'exchange_sale_id', 'authorized_by']);
        });

        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->dropColumn(['hsn_code', 'tax_rate', 'cgst', 'sgst', 'igst', 'discount_reason']);
        });

        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->dropForeign(['register_id']);
            $table->dropColumn(['register_id', 'notes']);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('pin');
        });
    }
};
