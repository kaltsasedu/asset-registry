<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\SystemBackup;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class SystemBackupService
{
    public function create(?int $userId = null, ?bool $includeFiles = null, bool $cleanup = true): SystemBackup
    {
        $this->ensureZipAvailable();
        $settings = ApplicationSetting::current();
        $includeFiles ??= (bool) $settings->backup_include_files;
        $fileName = 'backup-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.zip';
        $relativePath = 'system-backups/' . $fileName;
        $absolutePath = Storage::disk('local')->path($relativePath);
        $temporarySql = Storage::disk('local')->path('system-backups/.tmp-' . Str::uuid() . '.sql');

        File::ensureDirectoryExists(dirname($absolutePath));

        $backup = SystemBackup::create([
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'includes_database' => true,
            'includes_files' => $includeFiles,
            'status' => 'running',
            'integrity_status' => 'unknown',
            'created_by' => $userId,
            'started_at' => now(),
        ]);

        $zip = new ZipArchive();
        $zipOpened = false;

        try {
            $this->dumpDatabase($temporarySql);

            if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Δεν ήταν δυνατή η δημιουργία του αρχείου αντιγράφου ασφαλείας.');
            }
            $zipOpened = true;

            if (! $zip->addFile($temporarySql, 'database/database.sql')) {
                throw new RuntimeException('Δεν ήταν δυνατή η προσθήκη της βάσης δεδομένων στο αντίγραφο.');
            }

            $fileCount = 0;
            if ($includeFiles) {
                $fileCount += $this->addDirectoryToZip(
                    $zip,
                    storage_path('app/private'),
                    'files/private',
                    [Storage::disk('local')->path('system-backups')],
                );
                $fileCount += $this->addDirectoryToZip($zip, storage_path('app/public'), 'files/public');
            }

            $manifest = [
                'format' => 'asset-registry-backup',
                'version' => 1,
                'created_at' => now()->toIso8601String(),
                'application' => config('app.name'),
                'database_driver' => config('database.default'),
                'includes_database' => true,
                'includes_files' => $includeFiles,
                'file_count' => $fileCount,
            ];
            if (! $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
                throw new RuntimeException('Δεν ήταν δυνατή η προσθήκη του αρχείου ελέγχου στο αντίγραφο.');
            }

            if (! $zip->close()) {
                throw new RuntimeException('Δεν ήταν δυνατή η ολοκλήρωση του αρχείου αντιγράφου ασφαλείας.');
            }
            $zipOpened = false;

            $checksum = hash_file('sha256', $absolutePath);
            if (! is_string($checksum)) {
                throw new RuntimeException('Δεν ήταν δυνατός ο υπολογισμός του ελέγχου ακεραιότητας.');
            }

            $backup->update([
                'size_bytes' => File::size($absolutePath),
                'checksum_sha256' => $checksum,
                'status' => 'completed',
                'integrity_status' => 'valid',
                'completed_at' => now(),
                'verified_at' => now(),
                'error_message' => null,
            ]);

            if ($cleanup) {
                $this->cleanup((int) $settings->backup_retention_days);
            }

            return $backup->refresh();
        } catch (Throwable $exception) {
            if ($zipOpened) {
                $zip->close();
            }
            File::delete($absolutePath);
            $backup->update([
                'status' => 'failed',
                'integrity_status' => 'invalid',
                'error_message' => Str::limit($exception->getMessage(), 4000, ''),
                'completed_at' => now(),
            ]);

            throw $exception;
        } finally {
            File::delete($temporarySql);
        }
    }

    public function verify(SystemBackup $backup): bool
    {
        $this->ensureZipAvailable();

        if ($backup->status !== 'completed' || ! Storage::disk('local')->exists($backup->file_path)) {
            $backup->update(['integrity_status' => 'invalid', 'verified_at' => now()]);
            return false;
        }

        $absolutePath = Storage::disk('local')->path($backup->file_path);
        $actualChecksum = hash_file('sha256', $absolutePath);
        $zip = new ZipArchive();
        $opened = $zip->open($absolutePath, ZipArchive::CHECKCONS) === true;
        $hasRequiredFiles = false;
        if ($opened) {
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
            $databaseIndex = $zip->locateName('database/database.sql');
            $databaseStats = $databaseIndex === false ? false : $zip->statIndex($databaseIndex);
            $hasRequiredFiles = is_array($manifest)
                && ($manifest['format'] ?? null) === 'asset-registry-backup'
                && (int) ($manifest['version'] ?? 0) === 1
                && is_array($databaseStats)
                && (int) ($databaseStats['size'] ?? 0) > 0;
        }

        if ($opened) {
            $zip->close();
        }

        $valid = is_string($actualChecksum)
            && is_string($backup->checksum_sha256)
            && hash_equals($backup->checksum_sha256, $actualChecksum)
            && $hasRequiredFiles;

        $backup->update([
            'integrity_status' => $valid ? 'valid' : 'invalid',
            'verified_at' => now(),
            'size_bytes' => File::size($absolutePath),
        ]);

        return $valid;
    }

    public function restore(SystemBackup $backup, bool $restoreDatabase, bool $restoreFiles, int $userId): void
    {
        if (! $restoreDatabase && ! $restoreFiles) {
            throw new RuntimeException('Επιλέξτε επαναφορά βάσης δεδομένων ή συνημμένων αρχείων.');
        }
        if ($restoreFiles && ! $backup->includes_files) {
            throw new RuntimeException('Το επιλεγμένο αντίγραφο δεν περιλαμβάνει συνημμένα αρχεία.');
        }

        if (! $this->verify($backup)) {
            throw new RuntimeException('Η επαναφορά ακυρώθηκε επειδή το αντίγραφο δεν πέρασε τον έλεγχο ακεραιότητας.');
        }

        // Δημιουργείται πάντοτε σημείο επιστροφής πριν από οποιαδήποτε μεταβολή.
        $safetyBackup = $this->create($userId, true, false);
        $targetPath = Storage::disk('local')->path($backup->file_path);
        $temporaryDirectory = Storage::disk('local')->path('system-backups/restore-' . Str::uuid());
        File::ensureDirectoryExists($temporaryDirectory);
        $databaseRestoreStarted = false;

        Artisan::call('down', ['--retry' => 60]);

        try {
            if ($restoreDatabase) {
                $databaseRestoreStarted = true;
                $this->restoreDatabaseFromArchive($targetPath, $temporaryDirectory);
                Artisan::call('migrate', ['--force' => true]);
            }

            if ($restoreFiles) {
                $this->restoreFilesFromArchive($targetPath);
            }

            SystemBackup::query()->updateOrCreate(
                ['file_name' => $backup->file_name],
                [
                    'file_path' => $backup->file_path,
                    'includes_database' => $backup->includes_database,
                    'includes_files' => $backup->includes_files,
                    'size_bytes' => $backup->size_bytes,
                    'checksum_sha256' => $backup->checksum_sha256,
                    'status' => 'completed',
                    'integrity_status' => 'valid',
                    'completed_at' => $backup->completed_at,
                    'verified_at' => now(),
                    'restored_at' => now(),
                    'restored_by' => $userId,
                ],
            );
            SystemBackup::query()->updateOrCreate(
                ['file_name' => $safetyBackup->file_name],
                [
                    'file_path' => $safetyBackup->file_path,
                    'includes_database' => true,
                    'includes_files' => true,
                    'size_bytes' => $safetyBackup->size_bytes,
                    'checksum_sha256' => $safetyBackup->checksum_sha256,
                    'status' => 'completed',
                    'integrity_status' => 'valid',
                    'created_by' => $userId,
                    'started_at' => $safetyBackup->started_at,
                    'completed_at' => $safetyBackup->completed_at,
                    'verified_at' => $safetyBackup->verified_at,
                ],
            );

            Artisan::call('optimize:clear');
        } catch (Throwable $exception) {
            if ($databaseRestoreStarted || $restoreFiles) {
                try {
                    if ($databaseRestoreStarted) {
                        $this->restoreDatabaseFromArchive(
                            Storage::disk('local')->path($safetyBackup->file_path),
                            $temporaryDirectory,
                        );
                    }
                    if ($restoreFiles) {
                        $this->restoreFilesFromArchive(Storage::disk('local')->path($safetyBackup->file_path));
                    }
                    SystemBackup::query()->whereKey($safetyBackup->id)->update([
                        'status' => 'completed',
                        'integrity_status' => 'valid',
                        'size_bytes' => $safetyBackup->size_bytes,
                        'checksum_sha256' => $safetyBackup->checksum_sha256,
                        'completed_at' => $safetyBackup->completed_at,
                        'verified_at' => now(),
                    ]);
                } catch (Throwable $rollbackException) {
                    throw new RuntimeException(
                        'Η επαναφορά απέτυχε και δεν ολοκληρώθηκε η αυτόματη επιστροφή στο αντίγραφο ασφαλείας. '
                        . 'Χρησιμοποιήστε το αρχείο «' . $safetyBackup->file_name . '». Αιτία: '
                        . $rollbackException->getMessage(),
                        previous: $exception,
                    );
                }
            }

            throw $exception;
        } finally {
            File::deleteDirectory($temporaryDirectory);
            Artisan::call('up');
        }
    }

    public function delete(SystemBackup $backup): void
    {
        Storage::disk('local')->delete($backup->file_path);
        $backup->delete();
    }

    public function cleanup(int $retentionDays): int
    {
        $retentionDays = max(1, $retentionDays);
        $expired = SystemBackup::query()
            ->whereIn('status', ['completed', 'failed'])
            ->where('completed_at', '<', now()->subDays($retentionDays))
            ->get();

        foreach ($expired as $backup) {
            $this->delete($backup);
        }

        return $expired->count();
    }

    private function dumpDatabase(string $targetFile): void
    {
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $database = config('database.connections.sqlite.database');
            if (! is_string($database) || ! File::exists($database)) {
                throw new RuntimeException('Δεν βρέθηκε το αρχείο της βάσης SQLite.');
            }
            File::copy($database, $targetFile);
            return;
        }

        if ($connection !== 'mysql' && $connection !== 'mariadb') {
            throw new RuntimeException('Υποστηρίζονται αντίγραφα ασφαλείας για MySQL, MariaDB και SQLite.');
        }

        $config = config('database.connections.' . $connection);
        $executable = $this->findMysqlExecutable('mysqldump', config('backup.mysqldump_path'));
        $command = [
            $executable,
            '--host=' . ($config['host'] ?? '127.0.0.1'),
            '--port=' . ($config['port'] ?? 3306),
            '--user=' . ($config['username'] ?? 'root'),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--hex-blob',
            '--default-character-set=utf8mb4',
            (string) ($config['database'] ?? ''),
        ];

        $this->runProcess($command, null, $targetFile, (string) ($config['password'] ?? ''));
    }

    private function restoreDatabaseFromArchive(string $archivePath, string $temporaryDirectory): void
    {
        $connection = config('database.default');
        $zip = new ZipArchive();

        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Δεν ήταν δυνατό το άνοιγμα του αντιγράφου για επαναφορά.');
        }

        $sqlStream = $zip->getStream('database/database.sql');
        if (! is_resource($sqlStream)) {
            $zip->close();
            throw new RuntimeException('Το αντίγραφο δεν περιέχει βάση δεδομένων.');
        }

        $sqlFile = $temporaryDirectory . DIRECTORY_SEPARATOR . 'database.sql';
        $targetStream = fopen($sqlFile, 'wb');
        if (! is_resource($targetStream)) {
            fclose($sqlStream);
            $zip->close();
            throw new RuntimeException('Δεν ήταν δυνατή η προετοιμασία της βάσης για επαναφορά.');
        }

        try {
            stream_copy_to_stream($sqlStream, $targetStream);
        } finally {
            fclose($sqlStream);
            fclose($targetStream);
            $zip->close();
        }

        if ($connection === 'sqlite') {
            $database = config('database.connections.sqlite.database');
            if (! is_string($database) || $database === '') {
                throw new RuntimeException('Δεν έχει οριστεί αρχείο βάσης SQLite.');
            }
            File::copy($sqlFile, $database);
            return;
        }

        if ($connection !== 'mysql' && $connection !== 'mariadb') {
            throw new RuntimeException('Υποστηρίζεται επαναφορά για MySQL, MariaDB και SQLite.');
        }

        $config = config('database.connections.' . $connection);
        $executable = $this->findMysqlExecutable('mysql', config('backup.mysql_path'));
        $command = [
            $executable,
            '--host=' . ($config['host'] ?? '127.0.0.1'),
            '--port=' . ($config['port'] ?? 3306),
            '--user=' . ($config['username'] ?? 'root'),
            '--default-character-set=utf8mb4',
            (string) ($config['database'] ?? ''),
        ];

        $this->runProcess($command, $sqlFile, null, (string) ($config['password'] ?? ''));
    }

    private function restoreFilesFromArchive(string $archivePath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Δεν ήταν δυνατό το άνοιγμα των συνημμένων του αντιγράφου.');
        }

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if (! is_string($name) || str_ends_with($name, '/')) continue;

                $disk = null;
                $relativePath = null;
                if (str_starts_with($name, 'files/private/')) {
                    $disk = Storage::disk('local');
                    $relativePath = substr($name, strlen('files/private/'));
                } elseif (str_starts_with($name, 'files/public/')) {
                    $disk = Storage::disk('public');
                    $relativePath = substr($name, strlen('files/public/'));
                }

                if ($disk === null || ! $this->isSafeRelativePath((string) $relativePath)) continue;

                $stream = $zip->getStream($name);
                if (! is_resource($stream)) {
                    throw new RuntimeException('Αποτυχία ανάγνωσης συνημμένου από το αντίγραφο: ' . $name);
                }

                try {
                    if (! $disk->writeStream($relativePath, $stream)) {
                        throw new RuntimeException('Αποτυχία επαναφοράς συνημμένου: ' . $relativePath);
                    }
                } finally {
                    fclose($stream);
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function addDirectoryToZip(ZipArchive $zip, string $root, string $prefix, array $excludedRoots = []): int
    {
        if (! File::isDirectory($root)) return 0;

        $root = rtrim(str_replace('\\', '/', $root), '/');
        $excludedRoots = array_map(fn (string $path): string => rtrim(str_replace('\\', '/', $path), '/'), $excludedRoots);
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) continue;
            $path = str_replace('\\', '/', $file->getPathname());
            if (collect($excludedRoots)->contains(fn (string $excluded): bool => $path === $excluded || str_starts_with($path, $excluded . '/'))) continue;
            $relativePath = ltrim(substr($path, strlen($root)), '/');
            if ($relativePath === '') continue;
            if (! $zip->addFile($file->getPathname(), $prefix . '/' . $relativePath)) {
                throw new RuntimeException('Αποτυχία προσθήκης αρχείου στο αντίγραφο: ' . $relativePath);
            }
            $count++;
        }

        return $count;
    }

    private function findMysqlExecutable(string $name, mixed $configuredPath): string
    {
        if (is_string($configuredPath) && $configuredPath !== '' && File::exists($configuredPath)) {
            return $configuredPath;
        }

        $extension = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
        $candidates = [dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . $name . $extension];
        $pathEnvironment = getenv('PATH');
        if (is_string($pathEnvironment)) {
            foreach (explode(PATH_SEPARATOR, $pathEnvironment) as $directory) {
                if ($directory !== '') $candidates[] = rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . $name . $extension;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $laragonRoot = dirname(dirname(base_path()));
            $patterns = [
                str_replace('\\', '/', $laragonRoot) . '/bin/mysql/*/bin/' . $name . '.exe',
                'C:/laragon/bin/mysql/*/bin/' . $name . '.exe',
            ];
            foreach ($patterns as $pattern) {
                $candidates = array_merge($candidates, glob($pattern) ?: []);
            }
        } else {
            $candidates = array_merge($candidates, ['/usr/bin/' . $name, '/usr/local/bin/' . $name]);
        }

        foreach (array_reverse(array_unique($candidates)) as $candidate) {
            if (is_string($candidate) && File::isFile($candidate)) return $candidate;
        }

        throw new RuntimeException(
            'Δεν βρέθηκε το εργαλείο ' . $name . '. Ορίστε τη διαδρομή στην παράμετρο '
            . ($name === 'mysqldump' ? 'BACKUP_MYSQLDUMP_PATH' : 'BACKUP_MYSQL_PATH') . ' του αρχείου .env.',
        );
    }

    private function runProcess(array $command, ?string $inputFile, ?string $outputFile, string $password): void
    {
        $nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $descriptors = [
            0 => $inputFile ? ['file', $inputFile, 'rb'] : ['file', $nullDevice, 'rb'],
            1 => $outputFile ? ['file', $outputFile, 'wb'] : ['file', $nullDevice, 'wb'],
            2 => ['pipe', 'w'],
        ];
        $environment = getenv();
        $environment = is_array($environment) ? $environment : [];
        $environment['MYSQL_PWD'] = $password;
        $process = proc_open($command, $descriptors, $pipes, base_path(), $environment, ['bypass_shell' => true]);

        if (! is_resource($process)) {
            throw new RuntimeException('Δεν ήταν δυνατή η εκτέλεση του εργαλείου βάσης δεδομένων.');
        }

        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException('Το εργαλείο βάσης δεδομένων απέτυχε: ' . Str::limit(trim((string) $errorOutput), 1000, ''));
        }
    }

    private function isSafeRelativePath(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        return $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_contains($path, '../')
            && ! str_contains($path, "\0");
    }

    private function ensureZipAvailable(): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Η επέκταση PHP ZipArchive δεν είναι ενεργή. Ενεργοποιήστε την επέκταση zip στο Laragon.');
        }
    }
}
