<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">{{ $expense->voucherTitle() }} {{ $expense->voucher_number }}</h2>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('expenses.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md">Back to Expenses</a>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Print Voucher</button>
            </div>
        </div>
    </x-slot>

    @php
        $company = setting('company_name', config('app.name'));
        $inWords = amount_in_words($expense->amount);
    @endphp

    {{-- Laid out after the shop's pre-printed check voucher pad. --}}
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-sm p-8 print:shadow-none print:p-0 text-gray-900">
        @include('documents._header')

        <div class="grid grid-cols-[1fr_auto_1fr] items-end gap-4 mb-5">
            <span></span>
            <h2 class="text-2xl font-extrabold italic tracking-wide border-b-2 border-gray-900 leading-tight whitespace-nowrap">{{ strtoupper($expense->voucherTitle()) }}</h2>
            <p class="text-right font-bold leading-tight">
                {{ $expense->isCheck() ? 'CV' : 'PCV' }} No.
                <span class="block text-red-700 font-mono text-base">{{ $expense->voucher_number }}</span>
            </p>
        </div>

        <div class="grid grid-cols-5 gap-x-6 gap-y-2 text-sm">
            <div class="col-span-3 flex gap-2"><span class="font-semibold shrink-0">PAY TO:</span><span class="flex-1 border-b border-gray-700 px-1">{{ $expense->payee }}</span></div>
            <div class="col-span-2 flex gap-2"><span class="font-semibold shrink-0">DATE:</span><span class="flex-1 border-b border-gray-700 px-1">{{ $expense->expense_date->format('M d, Y') }}</span></div>
            <div class="col-span-3 flex gap-2"><span class="font-semibold shrink-0">ADDRESS:</span><span class="flex-1 border-b border-gray-700 px-1">{{ $expense->payee_address }}&nbsp;</span></div>
            <div class="col-span-2 flex gap-2">
                @if ($expense->isCheck())
                    <span class="font-semibold shrink-0">BANK, CHECK NO.:</span>
                    <span class="flex-1 border-b border-gray-700 px-1 whitespace-nowrap">{{ $expense->bank_name }} {{ $expense->check_number }}</span>
                @else
                    <span class="font-semibold shrink-0">CATEGORY:</span>
                    <span class="flex-1 border-b border-gray-700 px-1">{{ $expense->category }}</span>
                @endif
            </div>
        </div>

        <p class="text-sm mt-3 mb-1">representing payment of the following:</p>
        <table class="w-full text-sm border-y-2 border-gray-900">
            <thead>
                <tr class="border-b border-gray-900">
                    <th class="py-2 text-center tracking-[0.3em] font-bold">PARTICULARS</th>
                    <th class="py-2 w-44 text-center font-bold border-l border-gray-900">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <tr class="align-top">
                    <td class="py-3 px-3 h-40">
                        <div class="whitespace-pre-line">{{ $expense->description ?: $expense->category }}</div>
                        @if ($expense->isCheck())
                            <div class="text-xs text-gray-500 mt-2">
                                Category: {{ $expense->category }}{{ $expense->check_date ? ' · Check date: '.$expense->check_date->format('M d, Y') : '' }}
                            </div>
                        @endif
                    </td>
                    <td class="py-3 px-3 text-right border-l border-gray-900">{{ money($expense->amount) }}</td>
                </tr>
                <tr class="border-t border-gray-900 font-bold">
                    <td class="py-2 px-3 text-right">TOTAL</td>
                    <td class="py-2 px-3 text-right border-l border-gray-900">{{ money($expense->amount) }}</td>
                </tr>
            </tbody>
        </table>

        <p class="text-sm leading-7 mt-4">
            Received from <strong class="uppercase">{{ $company }}</strong>, the amount of
            <span class="border-b border-gray-700 px-1 italic">{{ $inWords ?? '' }}</span>
            (<strong>{{ money($expense->amount) }}</strong>) as full/partial payment of their account.
        </p>

        <div class="flex justify-end mt-8 text-sm">
            <div class="w-80 text-center">
                <p class="font-medium uppercase h-5">{{ $expense->payee }}</p>
                <p class="border-t border-gray-700 pt-1 text-xs">Payee's Printed Name &amp; Signature</p>
            </div>
        </div>

        <div class="grid grid-cols-3 border-y-2 border-gray-900 mt-4 text-sm">
            <div class="p-2 h-20 flex flex-col justify-between">
                <span class="font-semibold">Prepared By:</span>
                <span class="text-center">{{ $expense->user?->name }}</span>
            </div>
            <div class="p-2 h-20 flex flex-col justify-between border-x border-gray-900">
                <span class="font-semibold">Posted By:</span>
                <span>&nbsp;</span>
            </div>
            <div class="p-2 h-20 flex flex-col justify-between">
                <span class="font-semibold">Approved for payment:</span>
                <span class="text-center">{{ $expense->approved_by }}</span>
            </div>
        </div>
    </div>
</x-app-layout>
