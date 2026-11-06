<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payouts', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->change();
            $table->string('transaction_id', 128)->nullable()->after('payout_amount');
            $table->text('admin_notes')->nullable()->after('transaction_id');
            $table->timestamp('processed_at')->nullable()->after('admin_notes');
            $table->foreignId('processed_by_admin_id')->nullable()->after('processed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_payouts', function (Blueprint $table) {
            $table->dropForeign(['processed_by_admin_id']);
            $table->dropColumn(['transaction_id', 'admin_notes', 'processed_at', 'processed_by_admin_id']);
        });
    }
};
