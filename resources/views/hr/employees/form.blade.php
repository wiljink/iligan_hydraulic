<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">{{ $employee->exists ? 'Edit Employee' : 'Add Employee' }}</h2>
    </x-slot>

    @php
        $time = fn ($value) => $value ? substr($value, 0, 5) : '';
        $checked = fn ($field) => old($field, $employee->{$field}) ? 'checked' : '';
    @endphp

    <form method="POST" action="{{ $employee->exists ? route('hr.employees.update', $employee) : route('hr.employees.store') }}" class="max-w-4xl space-y-6">
        @csrf
        @if ($employee->exists) @method('PUT') @endif

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-4">Employee</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="employee_no" value="Employee No. (biometric ID) *" />
                    <x-text-input id="employee_no" name="employee_no" class="mt-1 block w-full font-mono" value="{{ old('employee_no', $employee->employee_no) }}" required />
                    <p class="mt-1 text-xs text-gray-400">Must match the User ID enrolled on the biometric device.</p>
                </div>
                <div>
                    <x-input-label for="first_name" value="First Name *" />
                    <x-text-input id="first_name" name="first_name" class="mt-1 block w-full" value="{{ old('first_name', $employee->first_name) }}" required />
                </div>
                <div>
                    <x-input-label for="last_name" value="Last Name *" />
                    <x-text-input id="last_name" name="last_name" class="mt-1 block w-full" value="{{ old('last_name', $employee->last_name) }}" required />
                </div>
                <div>
                    <x-input-label for="position" value="Position" />
                    <x-text-input id="position" name="position" class="mt-1 block w-full" value="{{ old('position', $employee->position) }}" />
                </div>
                <div>
                    <x-input-label for="branch_id" value="Branch" />
                    <select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">&mdash;</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id', $employee->branch_id) == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="date_hired" value="Date Hired" />
                    <x-text-input id="date_hired" type="date" name="date_hired" class="mt-1 block w-full" value="{{ old('date_hired', $employee->date_hired?->toDateString()) }}" />
                </div>
                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" class="mt-1 block w-full" value="{{ old('phone', $employee->phone) }}" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="address" value="Address" />
                    <x-text-input id="address" name="address" class="mt-1 block w-full" value="{{ old('address', $employee->address) }}" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-4">Pay &amp; Schedule</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="daily_rate" value="Daily Rate *" />
                    <x-text-input id="daily_rate" type="number" step="0.01" min="0" name="daily_rate" class="mt-1 block w-full" value="{{ old('daily_rate', $employee->daily_rate) }}" required />
                </div>
                <div>
                    <x-input-label for="schedule_in" value="Time In *" />
                    <x-text-input id="schedule_in" type="time" name="schedule_in" class="mt-1 block w-full" value="{{ old('schedule_in', $time($employee->schedule_in)) }}" required />
                </div>
                <div>
                    <x-input-label for="schedule_out" value="Time Out *" />
                    <x-text-input id="schedule_out" type="time" name="schedule_out" class="mt-1 block w-full" value="{{ old('schedule_out', $time($employee->schedule_out)) }}" required />
                </div>
                <div>
                    <x-input-label for="break_minutes" value="Unpaid Break (minutes) *" />
                    <x-text-input id="break_minutes" type="number" min="0" name="break_minutes" class="mt-1 block w-full" value="{{ old('break_minutes', $employee->break_minutes) }}" required />
                </div>
                <div>
                    <x-input-label for="grace_minutes" value="Grace Period (minutes) *" />
                    <x-text-input id="grace_minutes" type="number" min="0" name="grace_minutes" class="mt-1 block w-full" value="{{ old('grace_minutes', $employee->grace_minutes ?? 0) }}" required />
                    <p class="mt-1 text-xs text-gray-400">Arriving within this many minutes isn't counted as late.</p>
                </div>
                <div>
                    <x-input-label for="rest_day" value="Rest Day *" />
                    <select id="rest_day" name="rest_day" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        @foreach (\App\Models\Employee::DAYS as $i => $day)
                            <option value="{{ $i }}" @selected((int) old('rest_day', $employee->rest_day) === $i)>{{ $day }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-4">Government Numbers &amp; Contributions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <x-input-label for="sss_no" value="SSS No." />
                    <x-text-input id="sss_no" name="sss_no" class="mt-1 block w-full" value="{{ old('sss_no', $employee->sss_no) }}" />
                </div>
                <div>
                    <x-input-label for="philhealth_no" value="PhilHealth No." />
                    <x-text-input id="philhealth_no" name="philhealth_no" class="mt-1 block w-full" value="{{ old('philhealth_no', $employee->philhealth_no) }}" />
                </div>
                <div>
                    <x-input-label for="pagibig_no" value="Pag-IBIG No." />
                    <x-text-input id="pagibig_no" name="pagibig_no" class="mt-1 block w-full" value="{{ old('pagibig_no', $employee->pagibig_no) }}" />
                </div>
                <div>
                    <x-input-label for="tin_number" value="TIN" />
                    <x-text-input id="tin_number" name="tin_number" class="mt-1 block w-full" value="{{ old('tin_number', $employee->tin_number) }}" />
                </div>
            </div>
            <div class="flex flex-wrap gap-6 mt-4 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="deduct_sss" value="1" {{ $checked('deduct_sss') }} class="rounded border-gray-300 text-orange-600"> Deduct SSS</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="deduct_philhealth" value="1" {{ $checked('deduct_philhealth') }} class="rounded border-gray-300 text-orange-600"> Deduct PhilHealth</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="deduct_pagibig" value="1" {{ $checked('deduct_pagibig') }} class="rounded border-gray-300 text-orange-600"> Deduct Pag-IBIG</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" {{ $checked('is_active') }} class="rounded border-gray-300 text-orange-600"> Active (included in payroll)</label>
            </div>
        </div>

        <div class="flex gap-3">
            <x-primary-button>{{ $employee->exists ? 'Save Changes' : 'Add Employee' }}</x-primary-button>
            <a href="{{ route('hr.employees.index') }}" class="px-4 py-2 text-sm text-gray-500">Cancel</a>
        </div>
    </form>
</x-app-layout>
