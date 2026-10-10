<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_name');
            $table->string('reference_number')->nullable(); // Supplier DR or Invoice number
            $table->date('receipt_date');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, partial
            $table->string('payment_method')->nullable(); // cash, bank, check, credit
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->foreignId('account_payable_id')->nullable()->constrained('accounts_payable')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('received'); // received, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('reference_number');
            $table->index('receipt_date');
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->boolean('update_cost_price')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
