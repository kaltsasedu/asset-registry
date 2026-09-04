<?php

namespace App\Console\Commands;

use App\Models\SystemBackup;
use App\Models\User;
use App\Notifications\AssetAttentionNotification;
use App\Services\SystemBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class VerifySystemBackups extends Command
{
    protected $signature = 'system:verify-backups';
    protected $description = 'Επαληθεύει το SHA-256 και τη δομή όλων των διαθέσιμων αντιγράφων ασφαλείας.';

    public function handle(SystemBackupService $service): int
    {
        $valid = 0;
        $invalid = 0;

        SystemBackup::query()->where('status', 'completed')->orderBy('id')->each(function (SystemBackup $backup) use ($service, &$valid, &$invalid): void {
            try {
                if ($service->verify($backup)) {
                    $valid++;
                    return;
                }
            } catch (Throwable $exception) {
                report($exception);
            }

            $invalid++;
            try {
                $this->notifyInvalidBackup($backup);
            } catch (Throwable $notificationException) {
                report($notificationException);
            }
        });

        $this->info("Έγκυρα αντίγραφα: {$valid}. Μη έγκυρα: {$invalid}.");

        return $invalid === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function notifyInvalidBackup(SystemBackup $backup): void
    {
        $alertKey = 'system-backup-invalid:' . $backup->id;

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
                    'Μη έγκυρο αντίγραφο ασφαλείας',
                    'Το αρχείο «' . $backup->file_name . '» απέτυχε στον έλεγχο ακεραιότητας και αποκλείστηκε από επαναφορά.',
                    $alertKey,
                    url('/admin/system-backups'),
                    'danger',
                ));
            }
        }
    }
}
