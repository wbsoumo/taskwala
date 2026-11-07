<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaign_collaterals')) {
            Schema::table('campaign_collaterals', function (Blueprint $table) {
                if (!Schema::hasColumn('campaign_collaterals', 'category_preset')) {
                    $table->string('category_preset', 50)->default('general')->after('media_type');
                }
            });

            // Make file_path nullable for text-only promotional copy templates
            try {
                DB::statement('ALTER TABLE campaign_collaterals MODIFY file_path VARCHAR(255) NULL');
            } catch (\Throwable $e) {
                // Fallback if sqlite or doctrine
                try {
                    Schema::table('campaign_collaterals', function (Blueprint $table) {
                        $table->string('file_path')->nullable()->change();
                    });
                } catch (\Throwable $e2) {}
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('campaign_collaterals')) {
            Schema::table('campaign_collaterals', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_collaterals', 'category_preset')) {
                    $table->dropColumn('category_preset');
                }
            });
        }
    }
};
