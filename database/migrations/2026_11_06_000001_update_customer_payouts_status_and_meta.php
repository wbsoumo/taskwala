<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Convert status enum to varchar(32) so it accepts initiated, done, failed, cancel, pending
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE customer_payouts MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'pending'");
        } else {
            Schema::table('customer_payouts', function (Blueprint $table) {
                $table->string('status', 32)->default('pending')->change();
            });
        }

        // 2. Safely add metadata columns if they do not already exist
        Schema::table('customer_payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_payouts', 'transaction_id')) {
                $table->string('transaction_id', 128)->nullable()->after('payout_amount');
            }
            if (!Schema::hasColumn('customer_payouts', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('customer_payouts', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('admin_notes');
            }
            if (!Schema::hasColumn('customer_payouts', 'processed_by_admin_id')) {
                $table->unsignedBigInteger('processed_by_admin_id')->nullable()->after('processed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_payouts', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('customer_payouts', 'transaction_id')) {
                $columnsToDrop[] = 'transaction_id';
            }
            if (Schema::hasColumn('customer_payouts', 'admin_notes')) {
                $columnsToDrop[] = 'admin_notes';
            }
            if (Schema::hasColumn('customer_payouts', 'processed_at')) {
                $columnsToDrop[] = 'processed_at';
            }
            if (Schema::hasColumn('customer_payouts', 'processed_by_admin_id')) {
                $columnsToDrop[] = 'processed_by_admin_id';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
