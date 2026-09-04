<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_maintenances', function (Blueprint $table): void {
            $table->foreignId('assigned_to_user_id')->nullable()->after('technician')->constrained('users')->nullOnDelete();
            $table->foreignId('generated_from_id')->nullable()->after('next_service_at')->constrained('asset_maintenances')->nullOnDelete();
            $table->unique('generated_from_id');
        });
    }

    public function down(): void
    {
        Schema::table('asset_maintenances', function (Blueprint $table): void {
            $table->dropUnique(['generated_from_id']);
            $table->dropConstrainedForeignId('assigned_to_user_id');
            $table->dropConstrainedForeignId('generated_from_id');
        });
    }
};
