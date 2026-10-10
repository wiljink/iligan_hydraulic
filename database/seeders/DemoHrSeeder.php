<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\CashAdvance;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo employees with biometric punches for the last two weeks and one cash advance.
 * Safe to re-run: existing employees/punches are skipped.
 *
 *   php artisan db:seed --class=DemoHrSeeder
 */
class DemoHrSeeder extends Seeder
{
    public function run(): void
    {
        $people = [
            ['employee_no' => '1', 'first_name' => 'Rodel', 'last_name' => 'Abellana', 'position' => 'Store Supervisor', 'daily_rate' => 750, 'habit' => 'punctual'],
            ['employee_no' => '2', 'first_name' => 'Joy', 'last_name' => 'Cabahug', 'position' => 'Cashier', 'daily_rate' => 520, 'habit' => 'often_late'],
            ['employee_no' => '3', 'first_name' => 'Marlon', 'last_name' => 'Dagondon', 'position' => 'Hose Fabricator', 'daily_rate' => 600, 'habit' => 'forgets_out'],
            ['employee_no' => '4', 'first_name' => 'Ana', 'last_name' => 'Lumantas', 'position' => 'Stock Clerk', 'daily_rate' => 480, 'habit' => 'absent_once'],
        ];

        foreach ($people as $person) {
            $habit = $person['habit'];
            unset($person['habit']);

            $employee = Employee::firstOrCreate(['employee_no' => $person['employee_no']], $person + [
                'schedule_in' => '08:00', 'schedule_out' => '17:00', 'break_minutes' => 60, 'grace_minutes' => 5, 'rest_day' => 0,
                'date_hired' => now()->subYears(2)->toDateString(),
            ]);

            $this->punches($employee, $habit);
        }

        $marlon = Employee::where('employee_no', '3')->first();
        if (! $marlon->cashAdvances()->exists()) {
            CashAdvance::create([
                'employee_id' => $marlon->id, 'advance_date' => now()->subDays(12)->toDateString(),
                'amount' => 3000, 'deduction_per_payroll' => 1000, 'balance' => 3000, 'notes' => 'School enrollment',
            ]);
        }
    }

    private function punches(Employee $employee, string $habit): void
    {
        $day = now()->subDays(14)->startOfDay();
        $absentDone = false;

        while ($day->lt(now()->startOfDay())) {
            if ($day->dayOfWeek !== (int) $employee->rest_day) {
                $seed = crc32($employee->employee_no.$day->toDateString());
                $in = $day->copy()->setTime(7, 50 + $seed % 12);   // 7:50-8:01
                $out = $day->copy()->setTime(17, $seed % 15);      // 17:00-17:14

                if ($habit === 'often_late' && $seed % 3 === 0) {
                    $in = $day->copy()->setTime(8, 10 + $seed % 25);
                }
                if ($habit === 'absent_once' && ! $absentDone && $day->isWednesday()) {
                    $absentDone = true;
                    $day->addDay();

                    continue;
                }

                $this->log($employee, $in);
                $this->log($employee, $day->copy()->setTime(12, $seed % 5));
                $this->log($employee, $day->copy()->setTime(13, $seed % 4));
                if (! ($habit === 'forgets_out' && $seed % 5 === 0)) {
                    $this->log($employee, $out);
                }
            }
            $day->addDay();
        }
    }

    private function log(Employee $employee, Carbon $at): void
    {
        AttendanceLog::firstOrCreate(
            ['employee_no' => $employee->employee_no, 'punched_at' => $at->format('Y-m-d H:i:s')],
            ['employee_id' => $employee->id, 'source' => AttendanceLog::SOURCE_IMPORT]
        );
    }
}
