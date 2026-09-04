<?php

namespace App\Notifications;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssetAttentionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly string $alertKey,
        private readonly string $url,
        private readonly string $color = 'warning',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->color($this->color)
            ->actions([
                Action::make('open')
                    ->label('Προβολή')
                    ->button()
                    ->url($this->url),
            ])
            ->getDatabaseMessage();

        $message['alert_key'] = $this->alertKey;

        return $message;
    }
}
