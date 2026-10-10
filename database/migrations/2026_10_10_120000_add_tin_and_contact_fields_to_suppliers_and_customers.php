<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('tin_number', 50)->nullable()->after('address');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('tin_number', 50)->nullable()->after('address');
            $table->string('contact_person')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('tin_number');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['tin_number', 'contact_person']);
        });
    }
};
