<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // returned_amount: value of items taken back on credit memos.
        // refunded_amount: portion of those returns paid back to the customer (the rest reduced the balance).
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('returned_amount', 12, 2)->default(0)->after('amount_paid');
            $table->decimal('refunded_amount', 12, 2)->default(0)->after('returned_amount');
        });

        // Collection receipts: one row per payment received against an invoice.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('cash');
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_memos', function (Blueprint $table) {
            $table->id();
            $table->string('memo_number')->unique();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason');
            $table->decimal('total', 12, 2);
            $table->decimal('applied_to_balance', 12, 2)->default(0);
            $table->decimal('refunded', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('credit_memo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_memo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_memo_items');
        Schema::dropIfExists('credit_memos');
        Schema::dropIfExists('payments');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['returned_amount', 'refunded_amount']);
        });
    }
};
