<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every expense is paid out on a voucher: a Petty Cash Voucher (PCV-) or a Check Voucher (CV-).
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('voucher_type')->default('petty_cash')->after('id');
            $table->string('voucher_number')->nullable()->unique()->after('voucher_type');
            $table->string('payee')->nullable()->after('category');
            $table->string('bank_name')->nullable()->after('amount');
            $table->string('check_number')->nullable()->after('bank_name');
            $table->date('check_date')->nullable()->after('check_number');
            $table->string('approved_by')->nullable()->after('check_date');
        });

        // Give existing expenses petty cash voucher numbers, sequenced per expense date.
        $sequences = [];
        foreach (DB::table('expenses')->orderBy('expense_date')->orderBy('id')->get(['id', 'expense_date']) as $expense) {
            $day = date('Ymd', strtotime($expense->expense_date));
            $sequences[$day] = ($sequences[$day] ?? 0) + 1;

            DB::table('expenses')->where('id', $expense->id)->update([
                'voucher_number' => 'PCV-'.$day.'-'.str_pad((string) $sequences[$day], 4, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique(['voucher_number']);
            $table->dropColumn(['voucher_type', 'voucher_number', 'payee', 'bank_name', 'check_number', 'check_date', 'approved_by']);
        });
    }
};
