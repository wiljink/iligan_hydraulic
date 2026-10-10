<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            // Same number as the employee's User ID / PIN on the biometric device.
            $table->string('employee_no')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('position')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('daily_rate', 10, 2);
            $table->time('schedule_in')->default('08:00:00');
            $table->time('schedule_out')->default('17:00:00');
            $table->unsignedSmallInteger('break_minutes')->default(60);
            $table->unsignedSmallInteger('grace_minutes')->default(0);
            $table->unsignedTinyInteger('rest_day')->default(0); // 0 = Sunday ... 6 = Saturday
            $table->boolean('deduct_sss')->default(true);
            $table->boolean('deduct_philhealth')->default(true);
            $table->boolean('deduct_pagibig')->default(true);
            $table->string('sss_no')->nullable();
            $table->string('philhealth_no')->nullable();
            $table->string('pagibig_no')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->date('date_hired')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('name');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        // Raw punches, as recorded by the device (or entered manually to fix a missed punch).
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no');
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('punched_at');
            $table->string('source')->default('import'); // import | device | manual
            $table->string('device_serial')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['employee_no', 'punched_at']);
            $table->index('punched_at');
        });

        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('advance_date');
            $table->decimal('amount', 10, 2);
            $table->decimal('deduction_per_payroll', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->string('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('draft'); // draft | finalized
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['period_start', 'period_end']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('days_worked', 5, 2)->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('undertime_minutes')->default(0);
            $table->unsignedSmallInteger('incomplete_days')->default(0);
            $table->decimal('basic_pay', 10, 2)->default(0);
            $table->decimal('late_undertime_deduction', 10, 2)->default(0);
            $table->decimal('adjustments', 10, 2)->default(0); // + allowance/OT, - other deductions
            $table->decimal('gross_pay', 10, 2)->default(0);
            $table->decimal('sss', 10, 2)->default(0);
            $table->decimal('philhealth', 10, 2)->default(0);
            $table->decimal('pagibig', 10, 2)->default(0);
            $table->decimal('cash_advance', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2)->default(0);
            $table->decimal('sss_employer', 10, 2)->default(0);
            $table->decimal('philhealth_employer', 10, 2)->default(0);
            $table->decimal('pagibig_employer', 10, 2)->default(0);
            $table->string('adjustment_note')->nullable();
            $table->timestamps();

            $table->unique(['payroll_id', 'employee_id']);
        });

        // Which cash advances a payslip paid down (applied when the payroll is finalized).
        Schema::create('cash_advance_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_advance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_advance_deductions');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('cash_advances');
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('biometric_devices');
        Schema::dropIfExists('employees');
    }
};
