<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('assets', function (Blueprint $table) {

        $table->id();

        $table->string('asset_code')->unique();

        $table->string('name');

        $table->text('description')->nullable();

        $table->foreignId('asset_category_id');

        $table->foreignId('supplier_id')->nullable();

        $table->foreignId('location_id')->nullable();

        $table->foreignId('department_id')->nullable();

        $table->string('serial_number')->nullable();

        $table->string('manufacturer')->nullable();

        $table->string('model')->nullable();

        $table->date('purchase_date');

        $table->date('depreciation_start_date');

        $table->decimal('purchase_value', 15, 2);

        $table->decimal('vat_amount', 15, 2)
            ->default(0);

        $table->decimal('residual_value', 15, 2)
            ->default(0);

        $table->decimal('depreciation_rate', 5, 2);

        $table->integer('useful_life_years');

        $table->decimal('accumulated_depreciation', 15, 2)
            ->default(0);

        $table->decimal('book_value', 15, 2)
            ->default(0);

        $table->string('invoice_number')
            ->nullable();

        $table->date('invoice_date')
            ->nullable();

        $table->enum('status', [
            'active',
            'sold',
            'damaged',
            'inactive'
        ])->default('active');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
