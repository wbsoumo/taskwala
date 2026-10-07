<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postback_ip_whitelists', function (Blueprint $table) {
            $table->foreignId('postback_provider_id')->nullable()->change();
        });

        Schema::table('postback_logs', function (Blueprint $table) {
            $table->json('response_payload')->nullable()->after('payload');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('postback_provider_id')->nullable()->after('duplicate_conversion_rules')->constrained('postback_providers')->nullOnDelete();
            $table->string('postback_secret_key', 64)->nullable()->after('postback_provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['postback_provider_id']);
            $table->dropColumn('postback_provider_id');
        });

        Schema::table('postback_logs', function (Blueprint $table) {
            $table->dropColumn('response_payload');
        });

        Schema::table('postback_ip_whitelists', function (Blueprint $table) {
            $table->foreignId('postback_provider_id')->nullable(false)->change();
        });
    }
};
