<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Employees</h2>
            <a href="{{ route('hr.employees.create') }}" class="px-4 py-2 bg-orange-500 text-white text-sm rounded-md">Add Employee</a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
        <form method="GET" class="flex flex-wrap gap-3 mb-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, employee no., position..." class="rounded-md border-gray-300 shadow-sm text-sm flex-1 min-w-48">
            <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="active" @selected(request('status', 'active') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="all" @selected(request('status') === 'all')>All</option>
            </select>
            <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Filter</button>
        </form>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">Emp. No.</th>
                    <th class="py-2">Name</th>
                    <th class="py-2">Position</th>
                    <th class="py-2">Branch</th>
                    <th class="py-2">Schedule</th>
                    <th class="py-2 text-right">Daily Rate</th>
                    <th class="py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr class="border-b last:border-0 {{ $employee->is_active ? '' : 'text-gray-400' }}">
                        <td class="py-2 font-mono">{{ $employee->employee_no }}</td>
                        <td class="py-2 font-medium">{{ $employee->full_name }}</td>
                        <td class="py-2">{{ $employee->position }}</td>
                        <td class="py-2">{{ $employee->branch?->name }}</td>
                        <td class="py-2 whitespace-nowrap">
                            {{ \Illuminate\Support\Carbon::parse($employee->schedule_in)->format('g:i A') }} - {{ \Illuminate\Support\Carbon::parse($employee->schedule_out)->format('g:i A') }}
                            <span class="block text-xs text-gray-400">Rest day: {{ \App\Models\Employee::DAYS[$employee->rest_day] }}</span>
                        </td>
                        <td class="py-2 text-right">{{ money($employee->daily_rate) }}</td>
                        <td class="py-2 text-right whitespace-nowrap space-x-2">
                            <a href="{{ route('hr.attendance.show', $employee) }}" class="text-gray-700 hover:underline">Attendance</a>
                            <a href="{{ route('hr.employees.edit', $employee) }}" class="text-blue-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-400">No employees yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $employees->links() }}</div>
    </div>
</x-app-layout>
