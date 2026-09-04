<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_depreciation_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('depreciation_book_id')->constrained()->cascadeOnDelete();
            $table->date('depreciation_start_date');
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->decimal('depreciation_rate', 5, 2)->default(0);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->string('depreciation_timing', 30)->default('full_month');
            $table->unsignedInteger('useful_life_years')->default(1);
            $table->decimal('annual_depreciation', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('remaining_value', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['asset_id', 'depreciation_book_id'], 'asset_book_depreciation_profile_unique');
        });

        Schema::table('depreciation_entries', function (Blueprint $table): void {
            $table->foreignId('asset_depreciation_profile_id')->nullable()->after('asset_id')->constrained()->nullOnDelete();
        });

        DB::table('assets')->orderBy('id')->each(function (object $asset): void {
            if (! $asset->depreciation_book_id) return;
            $profileId = DB::table('asset_depreciation_profiles')->insertGetId([
                'asset_id' => $asset->id,
                'depreciation_book_id' => $asset->depreciation_book_id,
                'depreciation_start_date' => $asset->depreciation_start_date,
                'residual_value' => $asset->residual_value,
                'depreciation_rate' => $asset->depreciation_rate,
                'depreciation_method' => $asset->depreciation_method,
                'depreciation_timing' => $asset->depreciation_timing,
                'useful_life_years' => $asset->useful_life_years,
                'annual_depreciation' => $asset->annual_depreciation,
                'accumulated_depreciation' => $asset->accumulated_depreciation,
                'remaining_value' => $asset->remaining_value,
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('depreciation_entries')->where('asset_id', $asset->id)->where('depreciation_book_id', $asset->depreciation_book_id)->update(['asset_depreciation_profile_id' => $profileId]);
        });

    }

    public function down(): void
    {
        Schema::table('depreciation_entries', function (Blueprint $table): void { $table->dropConstrainedForeignId('asset_depreciation_profile_id'); });
        Schema::dropIfExists('asset_depreciation_profiles');
    }
};
