<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funding_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('funding_type', 40)->default('regular_budget');
            $table->string('program')->nullable();
            $table->string('project_code', 100)->nullable();
            $table->string('ops_code', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cost_centers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('procurement_contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('contract_number', 150)->unique();
            $table->string('title');
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('funding_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->date('signed_at')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->string('budget_code', 100)->nullable();
            $table->string('ada', 100)->nullable();
            $table->string('kimdis_reference', 150)->nullable();
            $table->string('decision_number', 150)->nullable();
            $table->string('status', 30)->default('active');
            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('asset_receipts', function (Blueprint $table): void {
            $table->foreignId('procurement_contract_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
            $table->foreignId('funding_source_id')->nullable()->after('procurement_contract_id')->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->after('funding_source_id')->constrained()->nullOnDelete();
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->foreignId('procurement_contract_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
            $table->foreignId('funding_source_id')->nullable()->after('procurement_contract_id')->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->after('funding_source_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cost_center_id');
            $table->dropConstrainedForeignId('funding_source_id');
            $table->dropConstrainedForeignId('procurement_contract_id');
        });

        Schema::table('asset_receipts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cost_center_id');
            $table->dropConstrainedForeignId('funding_source_id');
            $table->dropConstrainedForeignId('procurement_contract_id');
        });

        Schema::dropIfExists('procurement_contracts');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('funding_sources');
    }
};
