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
        Schema::table('assets', function (Blueprint $table) {

            $table->decimal(
                'annual_depreciation',
                15,
                2
            )->default(0);

            $table->decimal(
                'remaining_value',
                15,
                2
            )->default(0);

        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {

            $table->dropColumn([
                'annual_depreciation',
                'remaining_value',
            ]);

        });
    }
};
