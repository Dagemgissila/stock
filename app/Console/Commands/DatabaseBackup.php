<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Command
{
    protected $signature   = 'db:backup {--disk=local : Storage disk (local|s3)}';
    protected $description = 'Create a timestamped database backup dump';

    public function handle(): int
    {
        $disk      = $this->option('disk');
        $filename  = 'backup_' . now()->format('Y_m_d_His') . '.sql';
        $path      = storage_path('app/backups/' . $filename);

        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);

        $db   = config('database.connections.mysql');
        $cmd  = sprintf(
            'mysqldump -u%s -p%s %s > %s',
            escapeshellarg($db['username']),
            escapeshellarg($db['password']),
            escapeshellarg($db['database']),
            escapeshellarg($path)
        );

        exec($cmd, $output, $status);

        if ($status !== 0) {
            $this->error('Database backup failed.');
            return self::FAILURE;
        }

        if ($disk === 's3') {
            Storage::disk('s3')->put('backups/' . $filename, file_get_contents($path));
            unlink($path);
            $this->info('Backup uploaded to S3: ' . $filename);
        } else {
            $this->info('Backup saved locally: ' . $filename);
        }

        return self::SUCCESS;
    }
}
