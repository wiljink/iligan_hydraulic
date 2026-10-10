<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::with('branch')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->get('q').'%';
                $q->where(fn ($q) => $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('employee_no', 'like', $term)
                    ->orWhere('position', 'like', $term));
            })
            ->when($request->get('status', 'active') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->get('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        return view('hr.employees.index', compact('employees'));
    }

    public function create(): View
    {
        return view('hr.employees.form', ['employee' => new Employee(['schedule_in' => '08:00', 'schedule_out' => '17:00', 'break_minutes' => 60, 'rest_day' => 0, 'is_active' => true, 'deduct_sss' => true, 'deduct_philhealth' => true, 'deduct_pagibig' => true]), 'branches' => Branch::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = Employee::create($this->validated($request));

        return redirect()->route('hr.employees.index')->with('success', "Employee {$employee->full_name} added.");
    }

    public function edit(Employee $employee): View
    {
        return view('hr.employees.form', ['employee' => $employee, 'branches' => Branch::orderBy('name')->get()]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validated($request, $employee));

        return redirect()->route('hr.employees.index')->with('success', "Employee {$employee->full_name} updated.");
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $data = $request->validate([
            'employee_no' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_no')->ignore($employee?->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'schedule_in' => ['required', 'date_format:H:i'],
            'schedule_out' => ['required', 'date_format:H:i', 'after:schedule_in'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'rest_day' => ['required', 'integer', 'between:0,6'],
            'sss_no' => ['nullable', 'string', 'max:30'],
            'philhealth_no' => ['nullable', 'string', 'max:30'],
            'pagibig_no' => ['nullable', 'string', 'max:30'],
            'tin_number' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'date_hired' => ['nullable', 'date'],
        ]);

        foreach (['deduct_sss', 'deduct_philhealth', 'deduct_pagibig', 'is_active'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }
}
