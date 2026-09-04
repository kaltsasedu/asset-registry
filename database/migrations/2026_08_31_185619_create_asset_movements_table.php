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
    Schema::create('asset_movements', function (Blueprint $table) {

        $table->id();

        $table->foreignId('asset_id');

        $table->foreignId('from_location_id')
            ->nullable();

        $table->foreignId('to_location_id')
            ->nullable();

        $table->date('movement_date');

        $table->text('notes')
            ->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};
