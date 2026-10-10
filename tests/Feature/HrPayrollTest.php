<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\BiometricDevice;
use App\Models\CashAdvance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Services\Payroll\AttendanceSummary;
use App\Services\Payroll\BiometricImporter;
use App\Services\Payroll\GovernmentContributions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class HrPayrollTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        // 8:00-17:00 with a 1-hour break = 480 paid minutes, so P600/day = P1.25/minute.
        $this->employee = Employee::create([
            'employee_no' => '1', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'daily_rate' => 600,
            'schedule_in' => '08:00', 'schedule_out' => '17:00', 'break_minutes' => 60, 'grace_minutes' => 5, 'rest_day' => 0,
        ]);
    }

    private function punch(string $at, string $employeeNo = '1'): void
    {
        AttendanceLog::create(['employee_no' => $employeeNo, 'punched_at' => $at, 'source' => 'import']);
    }

    /** Oct 1-3, 2026 (Thu-Sat): on time (within grace), late + undertime, missing time-out. */
    private function seedAttendance(): void
    {
        $this->punch('2026-10-01 08:03:00');
        $this->punch('2026-10-01 12:01:00');
        $this->punch('2026-10-01 17:02:00');
        $this->punch('2026-10-02 08:20:00');
        $this->punch('2026-10-02 16:30:00');
        $this->punch('2026-10-03 07:58:00');
    }

    public function test_government_contribution_tables(): void
    {
        // P600 x 26 = P15,600/month -> SSS MSC P15,500
        $this->assertSame(15500, GovernmentContributions::sssMonthlySalaryCredit(15600));
        $this->assertSame(['employee' => 775.0, 'employer' => 1580.0], GovernmentContributions::sss(15600));
        $this->assertSame(['employee' => 390.0, 'employer' => 390.0], GovernmentContributions::philhealth(15600));
        $this->assertSame(['employee' => 200.0, 'employer' => 200.0], GovernmentContributions::pagibig(15600));

        // Floors and ceilings
        $this->assertSame(['employee' => 250.0, 'employer' => 510.0], GovernmentContributions::sss(4000));
        $this->assertSame(35000, GovernmentContributions::sssMonthlySalaryCredit(80000));
        $this->assertSame(['employee' => 250.0, 'employer' => 250.0], GovernmentContributions::philhealth(6000));
        $this->assertSame(['employee' => 2500.0, 'employer' => 2500.0], GovernmentContributions::philhealth(150000));
        $this->assertSame(['employee' => 15.0, 'employer' => 30.0], GovernmentContributions::pagibig(1500));
    }

    public function test_attendance_summary_counts_late_undertime_and_missing_punches(): void
    {
        $this->seedAttendance();

        $summary = app(AttendanceSummary::class)->forEmployee($this->employee, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-04'));

        $this->assertSame(3, $summary['days_worked']);
        $this->assertSame(20, $summary['late_minutes']);       // Oct 2; Oct 1's 3 minutes are within grace
        $this->assertSame(30, $summary['undertime_minutes']);  // left 16:30
        $this->assertSame(1, $summary['incomplete_days']);     // Oct 3 has only a time-in
        $this->assertSame('rest_day', $summary['days'][3]['status']); // Oct 4 is a Sunday
        $this->assertSame(0, $summary['absent_days']);
    }

    public function test_lunch_punches_without_time_out_are_flagged_not_counted_as_undertime(): void
    {
        $this->punch('2026-10-05 07:58:00');
        $this->punch('2026-10-05 12:00:00');
        $this->punch('2026-10-05 13:00:00'); // back from lunch, then forgot to time out

        $summary = app(AttendanceSummary::class)->forEmployee($this->employee, Carbon::parse('2026-10-05'), Carbon::parse('2026-10-05'));

        $this->assertSame('incomplete', $summary['days'][0]['status']);
        $this->assertSame(0, $summary['undertime_minutes']);
        $this->assertSame(1, $summary['days_worked']);
    }

    public function test_import_zkteco_usb_attlog_skips_duplicates_and_reports_unknown_ids(): void
    {
        $dat = "        1\t2026-10-01 08:00:00\t1\t0\t1\t0\n        1\t2026-10-01 17:00:00\t1\t1\t1\t0\n       99\t2026-10-01 08:10:00\t1\t0\t1\t0\ngarbage line\n";
        $file = UploadedFile::fake()->createWithContent('1_attlog.dat', $dat);

        $this->post(route('hr.attendance.import'), ['file' => $file])->assertSessionHas('warning');

        $this->assertSame(3, AttendanceLog::count());
        $this->assertSame(2, AttendanceLog::where('employee_id', $this->employee->id)->count());

        // Same file again: nothing new.
        $again = UploadedFile::fake()->createWithContent('1_attlog.dat', $dat);
        $result = app(BiometricImporter::class)->import($again->getRealPath(), '1_attlog.dat');
        $this->assertSame(0, $result['imported']);
        $this->assertSame(3, $result['duplicates']);

        // Adding employee 99 later links their earlier punch.
        $late = Employee::create(['employee_no' => '99', 'first_name' => 'Maria', 'last_name' => 'Santos', 'daily_rate' => 550]);
        $this->assertSame(1, AttendanceLog::where('employee_id', $late->id)->count());
    }

    public function test_import_excel_report_with_ac_no_and_time_columns(): void
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->fromArray([
            ['Attendance Report'],
            ['AC-No.', 'Name', 'Time', 'State'],
            ['1', 'Juan', '2026-10-05 07:55', 'C/In'],
            ['1', 'Juan', '2026-10-05 17:05', 'C/Out'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'att').'.xlsx';
        (new Xlsx($book))->save($path);

        $result = app(BiometricImporter::class)->import($path, 'report.xlsx');
        unlink($path);

        $this->assertSame(2, $result['imported']);
        $this->assertTrue(AttendanceLog::where('punched_at', '2026-10-05 17:05:00')->exists());
    }

    public function test_import_csv_with_separate_date_and_time_columns(): void
    {
        $csv = "Person ID,First Name,Date,Time\n1,Juan,2026-10-06,08:01:00\n1,Juan,2026-10-06,17:00:00\n";
        $path = tempnam(sys_get_temp_dir(), 'att').'.csv';
        file_put_contents($path, $csv);

        $result = app(BiometricImporter::class)->import($path, 'export.csv');
        unlink($path);

        $this->assertSame(2, $result['imported']);
        $this->assertTrue(AttendanceLog::where('punched_at', '2026-10-06 08:01:00')->exists());
    }

    public function test_payroll_computes_pay_deductions_and_finalizes_cash_advance(): void
    {
        $this->seedAttendance();
        $advance = CashAdvance::create(['employee_id' => $this->employee->id, 'advance_date' => '2026-09-20', 'amount' => 1000, 'deduction_per_payroll' => 500, 'balance' => 1000]);

        $this->post(route('hr.payroll.store'), ['period_start' => '2026-10-01', 'period_end' => '2026-10-15']);

        $payroll = Payroll::firstOrFail();
        $item = $payroll->items()->firstOrFail();

        $this->assertEquals(3, $item->days_worked);
        $this->assertEquals(1800, $item->basic_pay);
        $this->assertEquals(62.50, $item->late_undertime_deduction); // 50 min x P1.25
        $this->assertEquals(1737.50, $item->gross_pay);
        $this->assertEquals(387.50, $item->sss);       // half of P775
        $this->assertEquals(195, $item->philhealth);   // half of P390
        $this->assertEquals(100, $item->pagibig);      // half of P200
        $this->assertEquals(500, $item->cash_advance);
        $this->assertEquals(555, $item->net_pay);
        $this->assertEquals(790, $item->sss_employer);

        // Adjustment (e.g. overtime) flows into gross and net.
        $this->put(route('hr.payroll.items.adjust', $item), ['adjustments' => 150, 'adjustment_note' => 'Overtime 2 hrs']);
        $this->assertEquals(705, $item->fresh()->net_pay);

        // Cash advance is only posted on finalize.
        $this->assertEquals(1000, $advance->fresh()->balance);
        $this->post(route('hr.payroll.finalize', $payroll));
        $this->assertEquals(500, $advance->fresh()->balance);
        $this->assertTrue($payroll->fresh()->isFinalized());

        // Finalized payrolls are locked.
        $this->post(route('hr.payroll.recompute', $payroll))->assertSessionHas('error');
        $this->put(route('hr.payroll.items.adjust', $item), ['adjustments' => 0])->assertSessionHas('error');

        $this->get(route('hr.payroll.show', $payroll))->assertOk()->assertSee('Dela Cruz, Juan');
        $this->get(route('hr.payroll.payslips', $payroll))->assertOk()->assertSee('PAYSLIP')->assertSee(money(705));
    }

    public function test_recompute_picks_up_fixed_punches_and_keeps_adjustments(): void
    {
        $this->seedAttendance();
        $this->post(route('hr.payroll.store'), ['period_start' => '2026-10-01', 'period_end' => '2026-10-15']);
        $payroll = Payroll::firstOrFail();
        $item = $payroll->items()->first();
        $this->put(route('hr.payroll.items.adjust', $item), ['adjustments' => 100]);

        // HR adds the missing time-out for Oct 3.
        $this->post(route('hr.attendance.punches.store', $this->employee), ['punched_at' => '2026-10-03T17:00', 'note' => 'Forgot to time out']);
        $this->post(route('hr.payroll.recompute', $payroll));

        $item->refresh();
        $this->assertSame(0, $item->incomplete_days);
        $this->assertEquals(100, $item->adjustments);
    }

    public function test_zkteco_adms_push_only_accepts_registered_devices(): void
    {
        $this->get('/iclock/cdata?SN=UNKNOWN&options=all')->assertForbidden();

        BiometricDevice::create(['serial_number' => 'CQZ7232460001', 'name' => 'Main door']);

        $this->get('/iclock/cdata?SN=CQZ7232460001&options=all')->assertOk()->assertSee('GET OPTION FROM: CQZ7232460001');

        $body = "1\t2026-10-07 07:59:41\t0\t1\t0\t0\t0\n1\t2026-10-07 17:01:03\t1\t1\t0\t0\t0\n";
        $this->call('POST', '/iclock/cdata?SN=CQZ7232460001&table=ATTLOG&Stamp=9999', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $body)
            ->assertOk()
            ->assertSee('OK: 2');

        $this->assertSame(2, AttendanceLog::where('source', 'device')->where('device_serial', 'CQZ7232460001')->count());
        $this->assertNotNull(BiometricDevice::first()->last_seen_at);

        $this->get('/iclock/getrequest?SN=CQZ7232460001')->assertOk()->assertSee('OK');
    }

    public function test_only_hr_permission_can_open_hr_pages(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($cashier)->get(route('hr.employees.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('hr.payroll.index'))->assertForbidden();
    }

    public function test_hr_pages_render(): void
    {
        $this->seedAttendance();

        $this->get(route('hr.employees.index'))->assertOk()->assertSee('Dela Cruz, Juan');
        $this->get(route('hr.employees.create'))->assertOk();
        $this->get(route('hr.employees.edit', $this->employee))->assertOk();
        $this->get(route('hr.attendance.index', ['from' => '2026-10-01', 'to' => '2026-10-15']))->assertOk()->assertSee('Dela Cruz, Juan');
        $this->get(route('hr.attendance.show', [$this->employee, 'from' => '2026-10-01', 'to' => '2026-10-15']))->assertOk()->assertSee('Missing punch');
        $this->get(route('hr.cash-advances.index'))->assertOk();
        $this->get(route('hr.payroll.index'))->assertOk();
    }
}
