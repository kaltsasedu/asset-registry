<?php

namespace App\Console\Commands;

use App\Models\ApplicationSetting;
use App\Models\SystemBackup;
use App\Models\User;
use App\Notifications\AssetAttentionNotification;
use App\Services\SystemBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateSystemBackup extends Command
{
    protected $signature = 'system:backup {--scheduled : Εκτέλεση μόνο όταν είναι ενεργός και απαιτείται ο προγραμματισμός} {--database-only : Χωρίς συνημμένα αρχεία}';
    protected $description = 'Δημιουργεί ελεγμένο αντίγραφο ασφαλείας της βάσης δεδομένων και των συνημμένων.';

    public function handle(SystemBackupService $service): int
    {
        $settings = ApplicationSetting::current();

        if ($this->option('scheduled') && ! $this->isDue($settings)) {
            $this->info('Δεν απαιτείται νέο προγραμματισμένο αντίγραφο αυτή τη στιγμή.');
            return self::SUCCESS;
        }

        try {
            $backup = $service->create(
                null,
                $this->option('database-only') ? false : (bool) $settings->backup_include_files,
            );
            $this->info('Το αντίγραφο ολοκληρώθηκε: ' . $backup->file_name . ' (' . $backup->formatted_size . ').');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            try {
                $this->notifyFailure($exception);
            } catch (Throwable $notificationException) {
                report($notificationException);
            }
            $this->error('Η δημιουργία αντιγράφου απέτυχε: ' . $exception->getMessage());
            return self::FAILURE;
        }
    }

    private function isDue(ApplicationSetting $settings): bool
    {
        if (! $settings->backup_enabled) return false;

        $timezone = (string) config('backup.timezone', 'Europe/Athens');
        $currentTime = now($timezone);
        $scheduledTime = substr((string) ($settings->backup_time ?: '02:00'), 0, 5);
        if ($currentTime->format('H:i') < $scheduledTime) return false;

        $lastAttempt = SystemBackup::query()->latest('started_at')->first()?->started_at;
        if ($lastAttempt && $lastAttempt->copy()->timezone($timezone)->isSameDay($currentTime)) return false;

        $lastCompleted = SystemBackup::query()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first()?->completed_at;

        if ($lastCompleted === null) return true;

        return $settings->backup_frequency === 'weekly'
            ? $lastCompleted->lt(now()->subDays(7))
            : ! $lastCompleted->copy()->timezone($timezone)->isSameDay($currentTime);
    }

    private function notifyFailure(Throwable $exception): void
    {
        $alertKey = 'system-backup-failed:' . now()->toDateString();

        foreach (User::query()->get() as $user) {
            if (! $user->hasRole('Διαχειριστής') && ! $user->can('system-backups.view')) continue;

            $created = DB::table('asset_alert_deliveries')->insertOrIgnore([
                'user_id' => $user->id,
                'alert_key' => $alertKey,
                'alert_type' => 'system_backup',
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($created === 1) {
                $user->notify(new AssetAttentionNotification(
                    'Αποτυχία αντιγράφου ασφαλείας',
                    'Δεν δημιουργήθηκε το προγραμματισμένο αντίγραφο. ' . $exception->getMessage(),
                    $alertKey,
                    url('/admin/system-backups'),
                    'danger',
                ));
            }
        }
    }
}
