<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Payroll</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-5 h-fit">
            <h3 class="font-semibold mb-1">Run Payroll</h3>
            <p class="text-sm text-gray-500 mb-3">Computes every active employee's pay for the cut-off from their biometric attendance.</p>
            <form method="POST" action="{{ route('hr.payroll.store') }}" class="space-y-3" x-data="{
                setCutoff(half) {
                    const d = new Date(this.$refs.month.value + '-01T00:00:00');
                    const y = d.getFullYear(), m = d.getMonth();
                    const pad = n => String(n).padStart(2, '0');
                    const last = new Date(y, m + 1, 0).getDate();
                    this.$refs.start.value = `${y}-${pad(m + 1)}-${half === 1 ? '01' : '16'}`;
                    this.$refs.end.value = `${y}-${pad(m + 1)}-${half === 1 ? '15' : pad(last)}`;
                }
            }">
                @csrf
                <div>
                    <x-input-label value="Quick pick" />
                    <div class="flex gap-2 mt-1">
                        <input type="month" x-ref="month" value="{{ $nextStart->format('Y-m') }}" class="rounded-md border-gray-300 shadow-sm text-sm flex-1">
                        <button type="button" @click="setCutoff(1)" class="px-3 py-2 bg-gray-100 text-sm rounded-md">1-15</button>
                        <button type="button" @click="setCutoff(2)" class="px-3 py-2 bg-gray-100 text-sm rounded-md">16-end</button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <x-input-label for="period_start" value="Period start *" />
                        <x-text-input id="period_start" x-ref="start" type="date" name="period_start" class="mt-1 block w-full" value="{{ old('period_start', $nextStart->toDateString()) }}" required />
                    </div>
                    <div>
                        <x-input-label for="period_end" value="Period end *" />
                        <x-text-input id="period_end" x-ref="end" type="date" name="period_end" class="mt-1 block w-full" value="{{ old('period_end', $nextEnd->toDateString()) }}" required />
                    </div>
                </div>
                <x-primary-button>Compute Payroll</x-primary-button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Period</th>
                        <th class="py-2">Status</th>
                        <th class="py-2 text-right">Employees</th>
                        <th class="py-2 text-right">Gross</th>
                        <th class="py-2 text-right">Deductions</th>
                        <th class="py-2 text-right">Net Pay</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payrolls as $payroll)
                        <tr class="border-b last:border-0">
                            <td class="py-2"><a href="{{ route('hr.payroll.show', $payroll) }}" class="text-orange-600 hover:underline font-medium">{{ $payroll->periodLabel() }}</a></td>
                            <td class="py-2">
                                <span class="px-2 py-0.5 rounded text-xs {{ $payroll->isFinalized() ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ $payroll->isFinalized() ? 'Finalized' : 'Draft' }}</span>
                            </td>
                            <td class="py-2 text-right">{{ $payroll->items_count }}</td>
                            <td class="py-2 text-right">{{ money($payroll->total_gross) }}</td>
                            <td class="py-2 text-right">{{ money($payroll->total_deductions) }}</td>
                            <td class="py-2 text-right font-semibold">{{ money($payroll->total_net) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-gray-400">No payroll runs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $payrolls->links() }}</div>
        </div>
    </div>
</x-app-layout>
