<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaigns') && !Schema::hasColumn('campaigns', 'check_duplicate_ip')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->boolean('check_duplicate_ip')->default(false)->after('duplicate_conversion_rules');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('campaigns') && Schema::hasColumn('campaigns', 'check_duplicate_ip')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('check_duplicate_ip');
            });
        }
    }
};
