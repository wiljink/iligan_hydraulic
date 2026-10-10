<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type')->default('store'); // store, warehouse
            $table->string('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Per-branch stock on hand. products.current_stock remains the company-wide total.
        Schema::create('branch_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->foreignId('from_branch_id')->constrained('branches');
            $table->foreignId('to_branch_id')->constrained('branches');
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->integer('quantity');
            $table->timestamps();
        });

        foreach (['users', 'sales', 'purchases', 'stock_movements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        // Move all existing data into a default "Main Branch".
        $now = now();
        $mainId = DB::table('branches')->insertGetId([
            'code' => 'MAIN',
            'name' => 'Main Branch',
            'type' => 'store',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('products')->orderBy('id')->select(['id', 'current_stock'])->chunk(500, function ($products) use ($mainId, $now) {
            DB::table('branch_stocks')->insert($products->map(fn ($p) => [
                'branch_id' => $mainId,
                'product_id' => $p->id,
                'quantity' => $p->current_stock,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });

        DB::table('sales')->update(['branch_id' => $mainId]);
        DB::table('purchases')->update(['branch_id' => $mainId]);
        DB::table('stock_movements')->update(['branch_id' => $mainId]);
    }

    public function down(): void
    {
        foreach (['stock_movements', 'purchases', 'sales', 'users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('branch_stocks');
        Schema::dropIfExists('branches');
    }
};
