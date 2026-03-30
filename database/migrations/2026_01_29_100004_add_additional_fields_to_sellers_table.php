<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('sellers', 'store_name')) {
                $table->string('store_name')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('sellers', 'store_description')) {
                $table->text('store_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('sellers', 'phone')) {
                $table->string('phone', 20)->nullable()->after('banner_url');
            }
            if (!Schema::hasColumn('sellers', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('sellers', 'available_balance')) {
                $table->decimal('available_balance', 12, 2)->default(0)->after('commission_rate');
            }
            if (!Schema::hasColumn('sellers', 'pending_balance')) {
                $table->decimal('pending_balance', 12, 2)->default(0)->after('available_balance');
            }
            if (!Schema::hasColumn('sellers', 'payout_method')) {
                $table->string('payout_method', 50)->nullable()->after('total_orders');
            }
            if (!Schema::hasColumn('sellers', 'payout_email')) {
                $table->string('payout_email')->nullable()->after('payout_method');
            }
            if (!Schema::hasColumn('sellers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('payout_email');
            }
            if (!Schema::hasColumn('sellers', 'bank_account')) {
                $table->string('bank_account', 50)->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('sellers', 'bank_routing')) {
                $table->string('bank_routing', 50)->nullable()->after('bank_account');
            }
            if (!Schema::hasColumn('sellers', 'email_notifications')) {
                $table->boolean('email_notifications')->default(true)->after('settings');
            }
            if (!Schema::hasColumn('sellers', 'order_notifications')) {
                $table->boolean('order_notifications')->default(true)->after('email_notifications');
            }
            if (!Schema::hasColumn('sellers', 'review_notifications')) {
                $table->boolean('review_notifications')->default(true)->after('order_notifications');
            }
            if (!Schema::hasColumn('sellers', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('sellers', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('sellers', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $columns = [
                'store_name', 'store_description', 'phone', 'address',
                'available_balance', 'pending_balance', 'payout_method',
                'payout_email', 'bank_name', 'bank_account', 'bank_routing',
                'email_notifications', 'order_notifications', 'review_notifications',
                'rejection_reason', 'suspension_reason', 'suspended_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sellers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
