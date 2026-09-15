<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(BackupService $backups): View
    {
        return view('settings.backup', ['backups' => $backups->list()]);
    }

    public function run(Request $request, BackupService $backups): RedirectResponse
    {
        $onlyDb = $request->boolean('only_db', true);

        try {
            $backups->runBackup($onlyDb);
        } catch (\Throwable $e) {
            return back()->with('error', 'Backup failed: '.$e->getMessage());
        }

        return back()->with('success', 'Backup created successfully.');
    }

    public function download(string $filename, BackupService $backups): BinaryFileResponse
    {
        $path = $backups->fullPath($filename);
        abort_unless(file_exists($path), 404);

        return ResponseFacade::download($path);
    }

    public function destroy(string $filename, BackupService $backups): RedirectResponse
    {
        $backups->delete($filename);

        return back()->with('success', 'Backup deleted.');
    }

    public function restore(Request $request, BackupService $backups): RedirectResponse
    {
        $request->validate([
            'source' => ['required', 'in:upload,existing'],
            'file' => ['required_if:source,upload', 'nullable', 'file', 'mimes:zip,sql'],
            'existing_filename' => ['required_if:source,existing', 'nullable', 'string'],
            'confirm' => ['required', 'accepted'],
        ]);

        try {
            if ($request->get('source') === 'upload') {
                $uploaded = $request->file('file');
                $tempPath = $uploaded->getRealPath();
                $backups->restore($tempPath);
            } else {
                $path = $backups->fullPath($request->get('existing_filename'));
                abort_unless(file_exists($path), 404, 'Backup file not found.');
                $backups->restore($path);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Restore failed: '.$e->getMessage());
        }

        return back()->with('success', 'Database restored successfully.');
    }
}
