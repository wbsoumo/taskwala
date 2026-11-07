<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('affiliate_links')) {
            try {
                DB::statement("ALTER TABLE affiliate_links MODIFY status VARCHAR(32) NOT NULL DEFAULT 'active'");
            } catch (\Throwable $e) {
                try {
                    Schema::table('affiliate_links', function (Blueprint $table) {
                        $table->string('status', 32)->default('active')->change();
                    });
                } catch (\Throwable $e2) {}
            }
        }
    }

    public function down(): void
    {
    }
};
