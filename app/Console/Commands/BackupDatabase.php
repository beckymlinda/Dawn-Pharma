<?php

namespace App\Console\Commands;

use App\Mail\DatabaseBackupMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Dumps the MySQL database to a gzipped .sql file using PHP only, so it works on
 * shared hosting where mysqldump / exec() are not available. The file can be
 * restored with phpMyAdmin (Import) or: gunzip < file.sql.gz | mysql dbname
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--no-email : Do not email the backup}';

    protected $description = 'Back up the database to storage/app/backups and email a copy';

    private const INSERT_BATCH = 200;

    public function handle(): int
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql' && $connection->getDriverName() !== 'mariadb') {
            $this->error('backup:database only supports MySQL/MariaDB.');
            return self::FAILURE;
        }

        $dir = config('backup.path');
        File::ensureDirectoryExists($dir);

        $database = $connection->getDatabaseName();
        $file = $dir . DIRECTORY_SEPARATOR . $database . '-' . now()->format('Y-m-d_His') . '.sql.gz';

        try {
            $this->dump($file);
        } catch (\Throwable $e) {
            File::delete($file);
            Log::error('Database backup failed', ['error' => $e->getMessage()]);
            $this->error('Backup failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $sizeMb = round(filesize($file) / 1048576, 2);
        $this->info("Backup written: {$file} ({$sizeMb} MB)");

        $this->pruneOldBackups($dir);

        if (!$this->option('no-email')) {
            $this->emailBackup($file, $sizeMb);
        }

        return self::SUCCESS;
    }

    private function dump(string $file): void
    {
        $pdo = DB::connection()->getPdo();
        $gz = gzopen($file, 'wb6');

        gzwrite($gz, "-- Database backup of `" . DB::connection()->getDatabaseName() . "`\n");
        gzwrite($gz, "-- Created " . now()->toDateTimeString() . "\n\n");
        gzwrite($gz, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_NUM);

        foreach ($tables as [$table]) {
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM)[1];
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n\n");

            // Unbuffered so large tables are streamed rather than loaded into memory
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $rows = $pdo->query("SELECT * FROM `{$table}`", \PDO::FETCH_NUM);

            $batch = [];
            foreach ($rows as $row) {
                $batch[] = '(' . implode(',', array_map(
                    fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                    $row
                )) . ')';

                if (count($batch) >= self::INSERT_BATCH) {
                    gzwrite($gz, "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                gzwrite($gz, "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $batch) . ";\n");
            }
            $rows->closeCursor();
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

            gzwrite($gz, "\n");
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);
    }

    private function pruneOldBackups(string $dir): void
    {
        $cutoff = now()->subDays(config('backup.keep_days'))->getTimestamp();

        foreach (File::glob($dir . DIRECTORY_SEPARATOR . '*.sql.gz') as $old) {
            if (filemtime($old) < $cutoff) {
                File::delete($old);
            }
        }
    }

    private function emailBackup(string $file, float $sizeMb): void
    {
        $recipients = array_filter(array_map('trim', explode(',', (string) config('backup.email'))));
        if (!$recipients) {
            $this->line('BACKUP_EMAIL not set; backup kept on the server only.');
            return;
        }

        if ($sizeMb > config('backup.max_email_mb')) {
            Log::warning('Database backup too large to email', ['file' => $file, 'size_mb' => $sizeMb]);
            $this->warn("Backup is {$sizeMb} MB, over the email limit; kept on the server only.");
            return;
        }

        try {
            Mail::to($recipients)->send(new DatabaseBackupMail($file, $sizeMb));
            $this->info('Backup emailed to ' . implode(', ', $recipients));
        } catch (\Throwable $e) {
            Log::error('Database backup email failed', ['error' => $e->getMessage()]);
            $this->warn('Backup saved, but emailing it failed: ' . $e->getMessage());
        }
    }
}
