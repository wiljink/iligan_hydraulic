<?php

namespace App\Http\Controllers;

use App\Exports\ProductTemplateExport;
use App\Imports\ProductsImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    public function index(): View
    {
        return view('imports.index');
    }

    public function template(Request $request): BinaryFileResponse
    {
        $withProducts = $request->boolean('with_products');

        return Excel::download(
            new ProductTemplateExport($withProducts),
            $withProducts ? 'products-catalog.xlsx' : 'products-import-template.xlsx'
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new ProductsImport;
        Excel::import($import, $request->file('file'));

        $message = "Imported: {$import->created} created, {$import->updated} updated.";
        if (! empty($import->errors)) {
            $message .= ' '.count($import->errors).' row(s) had errors.';

            return back()->with('warning', $message)->with('import_errors', $import->errors);
        }

        return back()->with('success', $message);
    }
}
