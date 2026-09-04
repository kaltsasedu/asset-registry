<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetInsurance;
use App\Models\AssetMaintenance;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Notifications\AssetAttentionNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendAssetAlerts extends Command
{
    protected $signature = 'assets:send-alerts
        {--warranty-days= : Ημέρες προειδοποίησης εγγύησης — προαιρετική παράκαμψη ρύθμισης}
        {--insurance-days= : Ημέρες προειδοποίησης ασφάλισης — προαιρετική παράκαμψη ρύθμισης}
        {--maintenance-days= : Ημέρες προειδοποίησης συντήρησης — προαιρετική παράκαμψη ρύθμισης}';

    protected $description = 'Δημιουργεί ειδοποιήσεις για λήξεις εγγυήσεων, ασφαλίσεων και προγραμματισμένες συντηρήσεις.';

    public function handle(): int
    {
        $today = now()->startOfDay();
        $settings = ApplicationSetting::current();
        $recipients = User::query()->get();
        $sent = 0;

        foreach ($this->warrantyAlerts($today, $this->daysOption('warranty-days', $settings->warranty_warning_days)) as $alert) {
            $sent += $this->notifyRecipients($recipients, $alert);
        }

        foreach ($this->insuranceAlerts($today, $this->daysOption('insurance-days', $settings->insurance_warning_days)) as $alert) {
            $sent += $this->notifyRecipients($recipients, $alert);
        }

        foreach ($this->maintenanceAlerts($today, $this->daysOption('maintenance-days', $settings->maintenance_warning_days)) as $alert) {
            $sent += $this->notifyRecipients($recipients, $alert);
        }

        $this->info("Δημιουργήθηκαν {$sent} νέες ειδοποιήσεις.");

        return self::SUCCESS;
    }

    private function daysOption(string $name, int $default): int
    {
        $value = $this->option($name);

        return $value === null || $value === ''
            ? $default
            : max(0, (int) $value);
    }

    private function warrantyAlerts(Carbon $today, int $days): array
    {
        return Asset::query()
            ->whereNotNull('warranty_expires_at')
            ->whereDate('warranty_expires_at', '>=', $today)
            ->whereDate('warranty_expires_at', '<=', $today->copy()->addDays($days))
            ->orderBy('warranty_expires_at')
            ->get()
            ->map(fn (Asset $asset) => [
                'key' => "warranty:{$asset->id}:{$asset->warranty_expires_at->toDateString()}",
                'title' => "Λήξη εγγύησης: {$asset->asset_code}",
                'body' => "Το πάγιο «{$asset->name}» λήγει στις {$asset->warranty_expires_at->format('d/m/Y')}.",
                'url' => url('/admin/assets'),
                'type' => 'warranty',
                'permission' => 'assets.view',
            ])->all();
    }

    private function insuranceAlerts(Carbon $today, int $days): array
    {
        return AssetInsurance::query()
            ->with('asset')
            ->whereDate('expires_at', '>=', $today)
            ->whereDate('expires_at', '<=', $today->copy()->addDays($days))
            ->orderBy('expires_at')
            ->get()
            ->map(fn (AssetInsurance $insurance) => [
                'key' => "insurance:{$insurance->id}:{$insurance->expires_at->toDateString()}",
                'title' => "Λήξη ασφάλισης: {$insurance->asset?->asset_code}",
                'body' => "Το ασφαλιστήριο {$insurance->policy_number} για το πάγιο «{$insurance->asset?->name}» λήγει στις {$insurance->expires_at->format('d/m/Y')}.",
                'url' => url('/admin/asset-insurances'),
                'type' => 'insurance',
                'permission' => 'asset-insurances.view',
            ])->all();
    }

    private function maintenanceAlerts(Carbon $today, int $days): array
    {
        $deadline = $today->copy()->addDays($days);
        $scheduled = AssetMaintenance::query()
            ->with('asset')
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereNotNull('scheduled_at')
            ->whereDate('scheduled_at', '<=', $deadline)
            ->get();

        $nextService = AssetMaintenance::query()
            ->with('asset')
            ->whereNotNull('next_service_at')
            ->doesntHave('generatedMaintenance')
            ->whereDate('next_service_at', '<=', $deadline)
            ->get();

        return $scheduled->merge($nextService)->unique('id')->map(function (AssetMaintenance $maintenance) use ($today): array {
            $dueDate = $maintenance->next_service_at ?? $maintenance->scheduled_at;
            $isOverdue = $dueDate->lt($today);

            return [
                'key' => "maintenance:{$maintenance->id}:{$dueDate->toDateString()}",
                'title' => ($isOverdue ? 'Εκκρεμής συντήρηση: ' : 'Προγραμματισμένη συντήρηση: ') . $maintenance->asset?->asset_code,
                'body' => "Η εργασία «{$maintenance->title}» για το πάγιο «{$maintenance->asset?->name}» έχει ημερομηνία {$dueDate->format('d/m/Y')}.",
                'url' => url('/admin/asset-maintenances'),
                'type' => 'maintenance',
                'permission' => 'asset-maintenances.view',
                'color' => $isOverdue ? 'danger' : 'warning',
            ];
        })->values()->all();
    }

    private function notifyRecipients(iterable $recipients, array $alert): int
    {
        $sent = 0;

        foreach ($recipients as $recipient) {
            if (! $recipient->hasRole('Διαχειριστής') && ! $recipient->can($alert['permission'])) {
                continue;
            }

            $created = DB::table('asset_alert_deliveries')->insertOrIgnore([
                'user_id' => $recipient->id,
                'alert_key' => $alert['key'],
                'alert_type' => $alert['type'],
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($created === 1) {
                $recipient->notify(new AssetAttentionNotification(
                    $alert['title'],
                    $alert['body'],
                    $alert['key'],
                    $alert['url'],
                    $alert['color'] ?? 'warning',
                ));
                $sent++;
            }
        }

        return $sent;
    }
}
