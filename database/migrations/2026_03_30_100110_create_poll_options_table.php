<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('stats_summary')->nullable();
            $table->string('entity_type', 64)->nullable();
            $table->string('entity_id', 128)->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['poll_id', 'display_order'], 'poll_opt_poll_disp_idx');
            $table->index(['poll_id', 'status'], 'poll_opt_poll_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_options');
    }
};
