<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Attendance &mdash; {{ $employee->full_name }}</h2>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('hr.attendance.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md">All Employees</a>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Print DTR</button>
            </div>
        </div>
    </x-slot>

    @php
        $statusStyles = [
            'present' => 'text-green-700',
            'incomplete' => 'text-amber-600 font-medium',
            'absent' => 'text-red-600',
            'rest_day' => 'text-gray-400',
        ];
        $statusLabels = ['present' => 'Present', 'incomplete' => 'Missing punch', 'absent' => 'Absent', 'rest_day' => 'Rest day'];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
            <form method="GET" class="flex flex-wrap items-end gap-3 mb-4 print:hidden">
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Show</button>
            </form>

            <p class="text-sm text-gray-500 mb-3">
                #{{ $employee->employee_no }} &bull; {{ $from->format('M d') }} - {{ $to->format('M d, Y') }} &bull;
                Schedule {{ \Illuminate\Support\Carbon::parse($employee->schedule_in)->format('g:i A') }} - {{ \Illuminate\Support\Carbon::parse($employee->schedule_out)->format('g:i A') }}
            </p>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Date</th>
                        <th class="py-2">In</th>
                        <th class="py-2">Out</th>
                        <th class="py-2 text-right">Late</th>
                        <th class="py-2 text-right">Undertime</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Punches</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($days as $day)
                        <tr class="border-b last:border-0 {{ $day['rest_day'] ? 'bg-gray-50' : '' }}">
                            <td class="py-1.5 whitespace-nowrap">{{ $day['date']->format('D, M d') }}</td>
                            <td class="py-1.5 whitespace-nowrap">{{ $day['in']?->format('g:i A') }}</td>
                            <td class="py-1.5 whitespace-nowrap">{{ $day['out']?->format('g:i A') }}</td>
                            <td class="py-1.5 text-right">{{ $day['late'] ?: '' }}</td>
                            <td class="py-1.5 text-right">{{ $day['undertime'] ?: '' }}</td>
                            <td class="py-1.5 {{ $statusStyles[$day['status']] ?? '' }}">{{ $statusLabels[$day['status']] ?? '' }}</td>
                            <td class="py-1.5 text-xs text-gray-500">
                                @foreach ($day['punches'] as $punch)
                                    <span class="inline-flex items-center gap-1 mr-2 whitespace-nowrap">
                                        {{ $punch->punched_at->format('g:i A') }}
                                        @if ($punch->source === 'manual')
                                            <span class="text-amber-600" title="Added by hand: {{ $punch->note }}">(manual)</span>
                                            <form method="POST" action="{{ route('hr.attendance.punches.destroy', $punch) }}" class="inline print:hidden" onsubmit="return confirm('Remove this manual punch?')">
                                                @csrf @method('DELETE')
                                                <button class="text-red-500" title="Remove">&times;</button>
                                            </form>
                                        @endif
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t font-semibold">
                        <td class="py-2" colspan="3">{{ $days_worked }} day(s) worked &bull; {{ $absent_days }} absent</td>
                        <td class="py-2 text-right">{{ $late_minutes }}</td>
                        <td class="py-2 text-right">{{ $undertime_minutes }}</td>
                        <td class="py-2" colspan="2">{{ $incomplete_days ? $incomplete_days.' with missing punch' : '' }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="hidden print:grid grid-cols-2 gap-10 mt-12 text-xs text-gray-500 text-center">
                <div class="border-t border-gray-400 pt-1">Employee signature</div>
                <div class="border-t border-gray-400 pt-1">Verified by</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5 h-fit print:hidden">
            <h3 class="font-semibold mb-1">Add Missing Punch</h3>
            <p class="text-sm text-gray-500 mb-3">For a forgotten time-in/out or a device that was down. Manual punches are marked and can be removed.</p>
            <form method="POST" action="{{ route('hr.attendance.punches.store', $employee) }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="punched_at" value="Date & Time *" />
                    <x-text-input id="punched_at" type="datetime-local" name="punched_at" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="note" value="Reason *" />
                    <x-text-input id="note" name="note" class="mt-1 block w-full" placeholder="e.g. Forgot to time out" required />
                </div>
                <x-primary-button>Add Punch</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
