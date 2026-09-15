<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupService
{
    public function disk(): string
    {
        return config('backup.backup.destination.disks')[0] ?? 'local';
    }

    public function backupDirectory(): string
    {
        return config('backup.backup.name');
    }

    /**
     * @return array<int, array{filename:string, size:int, size_human:string, date:\Illuminate\Support\Carbon}>
     */
    public function list(): array
    {
        $disk = Storage::disk($this->disk());
        $dir = $this->backupDirectory();

        if (! $disk->exists($dir)) {
            return [];
        }

        $files = collect($disk->files($dir))
            ->filter(fn ($path) => str_ends_with($path, '.zip'))
            ->map(function ($path) use ($disk) {
                $size = $disk->size($path);

                return [
                    'path' => $path,
                    'filename' => basename($path),
                    'size' => $size,
                    'size_human' => $this->humanSize($size),
                    'date' => \Illuminate\Support\Carbon::createFromTimestamp($disk->lastModified($path)),
                ];
            })
            ->sortByDesc('date')
            ->values();

        return $files->all();
    }

    public function runBackup(bool $onlyDb = true): void
    {
        $options = ['--disable-notifications' => true];
        if ($onlyDb) {
            $options['--only-db'] = true;
        }

        $exitCode = Artisan::call('backup:run', $options);

        if ($exitCode !== 0) {
            throw new RuntimeException('Backup command failed: '.trim(Artisan::output()));
        }
    }

    public function delete(string $filename): void
    {
        $path = $this->backupDirectory().'/'.basename($filename);
        Storage::disk($this->disk())->delete($path);
    }

    public function fullPath(string $filename): string
    {
        return Storage::disk($this->disk())->path($this->backupDirectory().'/'.basename($filename));
    }

    /**
     * Restore the database from a backup zip (spatie/laravel-backup format) or a raw .sql file.
     */
    public function restore(string $absolutePath): void
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        $sqlPath = $extension === 'zip'
            ? $this->extractSqlFromZip($absolutePath)
            : $absolutePath;

        try {
            $this->importSql($sqlPath);
        } finally {
            if ($extension === 'zip' && $sqlPath !== $absolutePath && file_exists($sqlPath)) {
                @unlink($sqlPath);
            }
        }
    }

    private function extractSqlFromZip(string $zipPath): string
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Unable to open backup zip file.');
        }

        $sqlEntry = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^db-dumps[/\\\\].*\.sql$#i', $name)) {
                $sqlEntry = $name;
                break;
            }
        }

        if (! $sqlEntry) {
            $zip->close();
            throw new RuntimeException('No database dump (db-dumps/*.sql) found inside this backup zip.');
        }

        $tempDir = storage_path('app/tmp-restore');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $destination = $tempDir.'/'.uniqid('restore_').'.sql';
        $stream = $zip->getStream($sqlEntry);
        $out = fopen($destination, 'w');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);
        $zip->close();

        return $destination;
    }

    private function importSql(string $sqlPath): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        $binaryPath = trim((string) ($config['dump']['dump_binary_path'] ?? ''));
        $mysqlBinary = $binaryPath !== '' ? rtrim($binaryPath, '\\/').DIRECTORY_SEPARATOR.'mysql'.(PHP_OS_FAMILY === 'Windows' ? '.exe' : '') : 'mysql';

        $extraFile = tempnam(sys_get_temp_dir(), 'ihpos_cnf');
        $contents = "[client]\nuser={$config['username']}\nhost={$config['host']}\nport={$config['port']}\n";
        if (! empty($config['password'])) {
            $contents .= "password=\"{$config['password']}\"\n";
        }
        file_put_contents($extraFile, $contents);

        try {
            $process = new Process([
                $mysqlBinary,
                '--defaults-extra-file='.$extraFile,
                $config['database'],
            ]);
            $process->setInput(fopen($sqlPath, 'r'));
            $process->setTimeout(600);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('Database restore failed: '.$process->getErrorOutput());
            }
        } finally {
            @unlink($extraFile);
        }
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2).' '.$units[$i];
    }
}
