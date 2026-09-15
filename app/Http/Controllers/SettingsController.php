<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $settings = Setting::all_cached();

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_tin' => ['nullable', 'string', 'max:50'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'tax_inclusive' => ['sometimes', 'boolean'],
            'low_stock_default' => ['required', 'integer', 'min:0'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
        ]);

        $data['tax_inclusive'] = $request->boolean('tax_inclusive') ? '1' : '0';

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Settings updated.');
    }
}
