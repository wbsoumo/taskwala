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
        Schema::create('app_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('badge_text')->nullable();
            $table->enum('media_type', ['image', 'video'])->default('image');
            $table->text('media_url');
            $table->text('thumbnail_url')->nullable();
            $table->enum('action_type', ['none', 'campaign', 'deal', 'app_route', 'external_url'])->default('none');
            $table->text('action_value')->nullable();
            $table->boolean('auto_play_video')->default(true);
            $table->boolean('loop_video')->default(true);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'order']);
        });

        // Seed initial banners
        \Illuminate\Support\Facades\DB::table('app_sliders')->insert([
            [
                'title' => 'Kotak811 Zero Balance Account',
                'subtitle' => 'Earn flat ₹300 on every verified account opening. Instant tracking!',
                'badge_text' => 'HOT OFFER',
                'media_type' => 'image',
                'media_url' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=1200&q=80',
                'thumbnail_url' => null,
                'action_type' => 'app_route',
                'action_value' => '/campaigns',
                'auto_play_video' => true,
                'loop_video' => true,
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Flash Loot Deals & Price Drops',
                'subtitle' => 'Share curated Amazon & Flipkart deals with high profit commissions!',
                'badge_text' => 'LOOT DEALS',
                'media_type' => 'image',
                'media_url' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=1200&q=80',
                'thumbnail_url' => null,
                'action_type' => 'app_route',
                'action_value' => '/deals',
                'auto_play_video' => true,
                'loop_video' => true,
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_sliders');
    }
};
