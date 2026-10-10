<?php

namespace App\Services\Payroll;

/**
 * Monthly SSS, PhilHealth and Pag-IBIG contributions (2025 schedules).
 *
 * Update these constants when the agencies publish new tables:
 *  - SSS (RA 11199, 2025): 15% of Monthly Salary Credit - 5% employee, 10% employer,
 *    MSC from P5,000 to P35,000 in P500 steps, plus employer EC of P10 (MSC < P15,000) or P30.
 *  - PhilHealth (2024-2025): 5% of monthly basic salary, floor P10,000, ceiling P100,000, split equally.
 *  - Pag-IBIG (2024+): 2% each from employee and employer on up to P10,000 (max P200 each);
 *    employee share is 1% when monthly salary is P1,500 or less.
 */
class GovernmentContributions
{
    public const SSS_MSC_MIN = 5000;

    public const SSS_MSC_MAX = 35000;

    public const SSS_EMPLOYEE_RATE = 0.05;

    public const SSS_EMPLOYER_RATE = 0.10;

    public const PHILHEALTH_RATE = 0.05;

    public const PHILHEALTH_FLOOR = 10000;

    public const PHILHEALTH_CEILING = 100000;

    public const PAGIBIG_RATE = 0.02;

    public const PAGIBIG_MAX_SALARY = 10000;

    public static function sssMonthlySalaryCredit(float $monthlyCompensation): int
    {
        if ($monthlyCompensation < self::SSS_MSC_MIN + 250) {
            return self::SSS_MSC_MIN;
        }

        // Each P500 bracket runs from (MSC - 250) to (MSC + 249.99).
        $msc = (int) (floor(($monthlyCompensation - 250) / 500) * 500) + 500;

        return min($msc, self::SSS_MSC_MAX);
    }

    /** @return array{employee: float, employer: float} */
    public static function sss(float $monthlyCompensation): array
    {
        $msc = self::sssMonthlySalaryCredit($monthlyCompensation);
        $ec = $msc < 15000 ? 10 : 30;

        return [
            'employee' => round($msc * self::SSS_EMPLOYEE_RATE, 2),
            'employer' => round($msc * self::SSS_EMPLOYER_RATE + $ec, 2),
        ];
    }

    /** @return array{employee: float, employer: float} */
    public static function philhealth(float $monthlyBasic): array
    {
        $base = min(max($monthlyBasic, self::PHILHEALTH_FLOOR), self::PHILHEALTH_CEILING);
        $share = round($base * self::PHILHEALTH_RATE / 2, 2);

        return ['employee' => $share, 'employer' => $share];
    }

    /** @return array{employee: float, employer: float} */
    public static function pagibig(float $monthlyCompensation): array
    {
        $base = min($monthlyCompensation, self::PAGIBIG_MAX_SALARY);
        $employeeRate = $monthlyCompensation <= 1500 ? 0.01 : self::PAGIBIG_RATE;

        return [
            'employee' => round($base * $employeeRate, 2),
            'employer' => round($base * self::PAGIBIG_RATE, 2),
        ];
    }
}
