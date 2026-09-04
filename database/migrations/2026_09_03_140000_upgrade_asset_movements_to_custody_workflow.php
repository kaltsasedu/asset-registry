<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_movements', function (Blueprint $table): void {
            $table->foreignId('from_department_id')->nullable()->after('from_location_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->after('to_location_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('from_custodian_id')->nullable()->after('from_department_id')->constrained('asset_custodians')->nullOnDelete();
            $table->foreignId('to_custodian_id')->nullable()->after('to_department_id')->constrained('asset_custodians')->nullOnDelete();
            $table->text('reason')->nullable()->after('movement_date');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'completed'])->default('draft')->after('reason');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->date('delivered_at')->nullable()->after('approved_by');
            $table->date('received_at')->nullable()->after('delivered_at');
            $table->string('delivered_by_name')->nullable()->after('received_at');
            $table->string('received_by_name')->nullable()->after('delivered_by_name');
            $table->timestamp('delivered_confirmed_at')->nullable()->after('received_by_name');
            $table->timestamp('received_confirmed_at')->nullable()->after('delivered_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('asset_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('from_department_id');
            $table->dropConstrainedForeignId('to_department_id');
            $table->dropConstrainedForeignId('from_custodian_id');
            $table->dropConstrainedForeignId('to_custodian_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['reason', 'status', 'submitted_at', 'approved_at', 'delivered_at', 'received_at', 'delivered_by_name', 'received_by_name', 'delivered_confirmed_at', 'received_confirmed_at']);
        });
    }
};
