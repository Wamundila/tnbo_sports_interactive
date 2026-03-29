<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictor_seasons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('predictor_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 32)->default('draft');
            $table->json('scoring_config')->nullable();
            $table->text('rules_text')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['campaign_id', 'slug'], 'pred_season_cmp_slug_unq');
            $table->index(['campaign_id', 'status'], 'pred_season_cmp_stat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictor_seasons');
    }
};
