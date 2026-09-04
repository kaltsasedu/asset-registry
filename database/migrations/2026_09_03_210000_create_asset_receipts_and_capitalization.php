<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('receipt_number', 80)->unique();
            $table->date('receipt_date');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contract_number', 150)->nullable();
            $table->date('contract_date')->nullable();
            $table->string('invoice_number', 150)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('acceptance_protocol_number', 150)->nullable();
            $table->date('acceptance_protocol_date')->nullable();
            $table->string('invoice_file_path')->nullable();
            $table->string('acceptance_protocol_file_path')->nullable();
            $table->string('contract_file_path')->nullable();
            $table->enum('status', ['draft', 'capitalized'])->default('draft');
            $table->timestamp('capitalized_at')->nullable();
            $table->foreignId('capitalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['supplier_id', 'invoice_number']);
        });

        Schema::create('asset_receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_receipt_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_value', 15, 2);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->foreignId('asset_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('depreciation_book_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_custodian_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->date('depreciation_start_date')->nullable();
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->decimal('depreciation_rate', 5, 2);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->string('depreciation_timing', 30)->default('full_month');
            $table->unsignedInteger('useful_life_years');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->foreignId('asset_receipt_line_id')->nullable()->after('asset_category_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('asset_receipt_line_id');
        });

        Schema::dropIfExists('asset_receipt_lines');
        Schema::dropIfExists('asset_receipts');
    }
};
