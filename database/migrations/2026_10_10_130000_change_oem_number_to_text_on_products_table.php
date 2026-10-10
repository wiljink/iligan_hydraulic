<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A product can carry many OEM numbers (comma-separated), so a 255-char string is too short.
        Schema::table('products', function (Blueprint $table) {
            $table->text('oem_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('oem_number')->nullable()->change();
        });
    }
};
