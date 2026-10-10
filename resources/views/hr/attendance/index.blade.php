<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Attendance</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-1">Import from Biometrics</h3>
            <p class="text-sm text-gray-500 mb-3">
                Upload the ZKTeco attendance export: the USB download (<code>attlog.dat</code>) or an Excel/CSV report with the
                employee number (AC-No. / User ID) and date-time. Re-importing the same file is safe; duplicates are skipped.
            </p>
            <form method="POST" action="{{ route('hr.attendance.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                @csrf
                <input type="file" name="file" accept=".dat,.txt,.csv,.xls,.xlsx" required class="text-sm flex-1">
                <x-primary-button>Import Punches</x-primary-button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-1">Devices (live push)</h3>
            <p class="text-sm text-gray-500 mb-3">
                Register a ZKTeco device by serial number, then on the device set <em>Comm. &gt; Cloud Server Setting (ADMS)</em>
                to server <code class="text-xs">{{ request()->getHost() }}</code>, port {{ request()->getPort() }}. Punches then arrive automatically.
            </p>
            <table class="w-full text-sm mb-3">
                <tbody>
                    @forelse ($devices as $device)
                        <tr class="border-b last:border-0">
                            <td class="py-1.5">{{ $device->name }} <span class="text-xs text-gray-400 font-mono">{{ $device->serial_number }}</span></td>
                            <td class="py-1.5 text-xs {{ $device->last_seen_at?->gt(now()->subMinutes(10)) ? 'text-green-700' : 'text-gray-400' }}">
                                {{ $device->last_seen_at ? 'Last seen '.$device->last_seen_at->diffForHumans() : 'Not connected yet' }}
                            </td>
                            <td class="py-1.5 text-right">
                                <form method="POST" action="{{ route('hr.attendance.devices.destroy', $device) }}" onsubmit="return confirm('Remove this device?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 text-xs hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="py-1.5 text-gray-400">No devices registered.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <form method="POST" action="{{ route('hr.attendance.devices.store') }}" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @csrf
                <input type="text" name="serial_number" placeholder="Serial no. (SN)" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                <input type="text" name="name" placeholder="Name, e.g. Main door" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                <select name="branch_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">Branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                <button class="px-3 py-2 bg-gray-800 text-white text-sm rounded-md">Register</button>
            </form>
        </div>
    </div>

    @if ($unknownNumbers->isNotEmpty())
        <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            Punches were received for device ID(s) with no employee record: <strong>{{ $unknownNumbers->implode(', ') }}</strong>.
            <a href="{{ route('hr.employees.create') }}" class="underline">Add the employee</a> with that Employee No. and the punches will link automatically.
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
        <form method="GET" class="flex flex-wrap items-end gap-3 mb-4">
            <div>
                <label class="text-xs text-gray-500 block">From</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block">To</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Show</button>
            <span class="text-xs text-gray-400">Defaults to the current payroll cut-off.</span>
        </form>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">Employee</th>
                    <th class="py-2 text-right">Days Worked</th>
                    <th class="py-2 text-right">Absent</th>
                    <th class="py-2 text-right">Late (min)</th>
                    <th class="py-2 text-right">Undertime (min)</th>
                    <th class="py-2 text-right">Incomplete</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b last:border-0">
                        <td class="py-2"><span class="font-medium">{{ $row['employee']->full_name }}</span> <span class="text-xs text-gray-400 font-mono">#{{ $row['employee']->employee_no }}</span></td>
                        <td class="py-2 text-right">{{ $row['days_worked'] }}</td>
                        <td class="py-2 text-right {{ $row['absent_days'] ? 'text-red-600' : '' }}">{{ $row['absent_days'] }}</td>
                        <td class="py-2 text-right">{{ $row['late_minutes'] }}</td>
                        <td class="py-2 text-right">{{ $row['undertime_minutes'] }}</td>
                        <td class="py-2 text-right {{ $row['incomplete_days'] ? 'text-amber-600 font-medium' : '' }}">{{ $row['incomplete_days'] }}</td>
                        <td class="py-2 text-right"><a href="{{ route('hr.attendance.show', [$row['employee'], 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="text-orange-600 hover:underline">Daily log</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-400">No active employees. <a href="{{ route('hr.employees.create') }}" class="underline">Add one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
