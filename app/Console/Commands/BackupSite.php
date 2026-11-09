<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupSite extends Command
{
    protected $signature = 'site:backup {--keep= : Number of archives to keep (default from config/backup.php)}';

    protected $description = 'Create a zip with a database dump and all uploaded media in storage/app/backups';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $stamp = now()->format('Ymd-His');
        $workDir = $dir.'/.work-'.$stamp;
        File::ensureDirectoryExists($workDir);

        try {
            $dbFile = $this->dumpDatabase($workDir);
            if ($dbFile === null) {
                return self::FAILURE;
            }

            $zipPath = "{$dir}/magicframes-{$stamp}.zip";
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->error('Could not create the archive.');

                return self::FAILURE;
            }
            $zip->addFile($dbFile, basename($dbFile));

            $media = storage_path('app/public');
            $count = 0;
            if (is_dir($media)) {
                foreach (File::allFiles($media) as $file) {
                    $relative = str_replace('\\', '/', $file->getRelativePathname());
                    if (str_starts_with($relative, '_cache/')) {
                        continue; // regenerated automatically
                    }
                    $zip->addFile($file->getRealPath(), 'media/'.$relative);
                    $count++;
                }
            }
            $zip->close();
        } finally {
            File::deleteDirectory($workDir);
        }

        $this->prune($dir, (int) ($this->option('keep') ?: config('backup.keep')));
        $this->info(sprintf('Backup created: %s (%s, %d media files)', $zipPath, $this->human(filesize($zipPath)), $count));

        return self::SUCCESS;
    }

    /** @return string|null path of the dump inside $workDir */
    private function dumpDatabase(string $workDir): ?string
    {
        $conn = config('database.default');
        $cfg = config("database.connections.{$conn}");

        if ($cfg['driver'] === 'sqlite') {
            $db = $cfg['database'];
            if ($db === ':memory:' || ! is_file($db)) {
                $this->error('SQLite database file not found; nothing to back up.');

                return null;
            }
            $target = $workDir.'/database.sqlite';
            File::copy($db, $target);

            return $target;
        }

        if (! in_array($cfg['driver'], ['mysql', 'mariadb'], true)) {
            $this->error("Unsupported database driver: {$cfg['driver']}");

            return null;
        }

        $target = $workDir.'/database.sql';
        $process = new Process([
            config('backup.mysqldump_path'),
            '--single-transaction', '--quick', '--routines', '--no-tablespaces', '--default-character-set=utf8mb4',
            '--host='.$cfg['host'], '--port='.$cfg['port'], '--user='.$cfg['username'],
            '--result-file='.$target, $cfg['database'],
        ], null, ['MYSQL_PWD' => (string) $cfg['password']], null, 600);

        try {
            $process->run();
        } catch (\Throwable $e) {
            $this->error('Could not run '.config('backup.mysqldump_path').': '.$e->getMessage());

            return null;
        }
        if (! $process->isSuccessful() || ! is_file($target) || filesize($target) === 0) {
            $this->error('Database dump failed: '.trim($process->getErrorOutput()));

            return null;
        }

        return $target;
    }

    private function prune(string $dir, int $keep): void
    {
        $archives = collect(File::glob($dir.'/magicframes-*.zip'))->sortDesc()->values();
        $archives->slice(max(1, $keep))->each(fn ($old) => File::delete($old));
    }

    private function human(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : round($bytes / 1024, 1).' KB';
    }
}
