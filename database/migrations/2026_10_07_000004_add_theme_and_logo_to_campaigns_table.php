<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('short_description');
            $table->string('theme', 50)->default('gradient_blue')->after('logo_url');
        });

        Schema::table('customer_payouts', function (Blueprint $table) {
            $table->foreignId('conversion_id')->nullable()->change();
            $table->foreignId('link_id')->nullable()->constrained('affiliate_links')->nullOnDelete();
            $table->string('click_id', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('customer_payouts', function (Blueprint $table) {
            $table->dropColumn(['link_id', 'click_id']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['logo_url', 'theme']);
        });
    }
};
