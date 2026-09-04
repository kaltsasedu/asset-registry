<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void { $table->date('warranty_expires_at')->nullable()->after('invoice_date'); });
        Schema::create('saved_asset_searches', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('name');
            $table->string('status', 40)->nullable(); $table->foreignId('asset_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('without_custodian')->default(false); $table->boolean('without_serial')->default(false); $table->boolean('without_invoice')->default(false); $table->boolean('without_location')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('saved_asset_searches'); Schema::table('assets', function (Blueprint $table): void { $table->dropColumn('warranty_expires_at'); }); }
};
