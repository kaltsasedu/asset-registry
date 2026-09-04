<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('active', 'sold', 'damaged', 'inactive', 'under_receipt', 'in_use', 'under_repair', 'in_inventory', 'pending_disposal', 'retired') NOT NULL DEFAULT 'active'");

        DB::table('assets')->where('status', 'active')->update(['status' => 'in_use']);
        DB::table('assets')->where('status', 'damaged')->update(['status' => 'under_repair']);
        DB::table('assets')->where('status', 'inactive')->update(['status' => 'pending_disposal']);

        DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('under_receipt', 'in_use', 'under_repair', 'in_inventory', 'pending_disposal', 'retired', 'sold') NOT NULL DEFAULT 'under_receipt'");

        Schema::create('asset_status_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type', 30);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason');
            $table->string('document_number')->nullable();
            $table->date('document_date')->nullable();
            $table->string('document_path')->nullable();
            $table->decimal('purchase_value', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('book_value', 15, 2)->default(0);
            $table->decimal('disposal_proceeds', 15, 2)->default(0);
            $table->decimal('gain_loss', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['asset_id', 'transaction_type']);
        });

        DB::table('assets')->orderBy('id')->each(function (object $asset): void {
            DB::table('asset_status_transactions')->insert([
                'asset_id' => $asset->id,
                'transaction_type' => 'initial_registration',
                'from_status' => null,
                'to_status' => $asset->status,
                'reason' => 'Αρχική καταχώριση / μεταφορά υφιστάμενου παγίου στο μητρώο.',
                'purchase_value' => $asset->purchase_value ?? 0,
                'accumulated_depreciation' => $asset->accumulated_depreciation ?? 0,
                'book_value' => $asset->remaining_value ?? 0,
                'disposal_proceeds' => 0,
                'created_at' => $asset->created_at ?? now(),
                'updated_at' => $asset->updated_at ?? now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_status_transactions');

        DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('active', 'sold', 'damaged', 'inactive', 'under_receipt', 'in_use', 'under_repair', 'in_inventory', 'pending_disposal', 'retired') NOT NULL DEFAULT 'active'");
        DB::table('assets')->whereIn('status', ['under_receipt', 'in_use', 'in_inventory'])->update(['status' => 'active']);
        DB::table('assets')->where('status', 'under_repair')->update(['status' => 'damaged']);
        DB::table('assets')->whereIn('status', ['pending_disposal', 'retired'])->update(['status' => 'inactive']);
        DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('active', 'sold', 'damaged', 'inactive') NOT NULL DEFAULT 'active'");
    }
};
