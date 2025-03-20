<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
class DatabaseBackup extends Command
{
    protected $signature='db:backup {--disk=local : Storage disk}';
    protected $description='Create a timestamped database backup';
    public function handle():int{
        $disk=$this->option('disk');
        $file='backup_'.now()->format('Y_m_d_His').'.sql';
        $path=storage_path('app/backups/'.$file);
        if(!is_dir(dirname($path)))mkdir(dirname($path),0755,true);
        $db=config('database.connections.mysql');
        $cmd=sprintf('mysqldump -u%s -p%s %s > %s',
            escapeshellarg($db['username']),escapeshellarg($db['password']),
            escapeshellarg($db['database']),escapeshellarg($path));
        exec($cmd,$out,$status);
        if($status!==0){$this->error('Backup failed.');return self::FAILURE;}
        if($disk==='s3'){Storage::disk('s3')->put('backups/'.$file,file_get_contents($path));unlink($path);$this->info('Uploaded to S3: '.$file);}
        else{$this->info('Saved locally: '.$file);}
        return self::SUCCESS;
    }
}
