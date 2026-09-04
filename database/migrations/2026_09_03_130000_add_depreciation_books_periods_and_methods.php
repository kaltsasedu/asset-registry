<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciation_books', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $bookId = DB::table('depreciation_books')->insertGetId([
            'name' => 'Λογιστικό βιβλίο',
            'code' => 'LOGISTIKO',
            'description' => 'Το αρχικό βιβλίο αποσβέσεων της εφαρμογής.',
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('depreciation_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('depreciation_book_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->enum('status', ['draft', 'posted', 'locked'])->default('draft');
            $table->timestamp('previewed_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['depreciation_book_id', 'year', 'month'], 'depreciation_period_unique');
        });

        Schema::table('asset_categories', function (Blueprint $table): void {
            $table->string('default_depreciation_method', 30)->default('straight_line')->after('default_rate');
            $table->string('default_depreciation_timing', 30)->default('full_month')->after('default_depreciation_method');
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->foreignId('depreciation_book_id')->nullable()->after('asset_category_id')->constrained()->nullOnDelete();
            $table->string('depreciation_method', 30)->default('straight_line')->after('depreciation_rate');
            $table->string('depreciation_timing', 30)->default('full_month')->after('depreciation_method');
        });

        Schema::table('depreciation_entries', function (Blueprint $table): void {
            $table->foreignId('depreciation_book_id')->nullable()->after('asset_id')->constrained()->nullOnDelete();
            $table->foreignId('depreciation_period_id')->nullable()->after('depreciation_book_id')->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'posted'])->default('posted')->after('book_value');
            $table->timestamp('posted_at')->nullable()->after('status');
        });

        DB::table('assets')->update(['depreciation_book_id' => $bookId]);
        DB::table('depreciation_entries')->update(['depreciation_book_id' => $bookId, 'posted_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('depreciation_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('depreciation_period_id');
            $table->dropConstrainedForeignId('depreciation_book_id');
            $table->dropColumn(['status', 'posted_at']);
        });
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('depreciation_book_id');
            $table->dropColumn(['depreciation_method', 'depreciation_timing']);
        });
        Schema::table('asset_categories', function (Blueprint $table): void {
            $table->dropColumn(['default_depreciation_method', 'default_depreciation_timing']);
        });
        Schema::dropIfExists('depreciation_periods');
        Schema::dropIfExists('depreciation_books');
    }
};
