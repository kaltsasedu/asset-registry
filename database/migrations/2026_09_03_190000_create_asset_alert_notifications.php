<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::create('asset_alert_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('alert_key', 191);
            $table->string('alert_type', 50);
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->unique(['user_id', 'alert_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_alert_deliveries');
        // Ο πίνακας notifications μπορεί να χρησιμοποιείται και από άλλες λειτουργίες.
    }
};
