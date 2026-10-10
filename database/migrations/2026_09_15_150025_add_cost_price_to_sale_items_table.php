<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->default(0)->after('unit_price');
        });

        // Backfill existing rows with the product's current cost price (best effort for historical data).
        if (DB::table('sale_items')->exists()) {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('UPDATE sale_items SET cost_price = COALESCE((SELECT cost_price FROM products WHERE products.id = sale_items.product_id), 0)');
            } else {
                DB::table('sale_items')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->update(['sale_items.cost_price' => DB::raw('products.cost_price')]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
