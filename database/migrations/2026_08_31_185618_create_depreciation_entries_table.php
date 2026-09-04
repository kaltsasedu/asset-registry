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
    Schema::create('depreciation_entries', function (Blueprint $table) {

        $table->id();

        $table->foreignId('asset_id');

        $table->integer('year');

        $table->integer('month');

        $table->decimal('amount', 15, 2);

        $table->decimal('book_value', 15, 2);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depreciation_entries');
    }
};
