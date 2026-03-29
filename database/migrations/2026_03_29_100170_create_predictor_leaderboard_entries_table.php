<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictor_leaderboard_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('leaderboard_type', 32);
            $table->foreignId('campaign_id')->constrained('predictor_campaigns')->cascadeOnDelete();
            $table->foreignId('season_id')->nullable()->constrained('predictor_seasons')->nullOnDelete();
            $table->foreignId('round_id')->nullable()->constrained('predictor_rounds')->nullOnDelete();
            $table->string('leaderboard_period_key')->nullable();
            $table->string('user_id', 64);
            $table->string('display_name_snapshot')->nullable();
            $table->string('avatar_url_snapshot')->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->decimal('points_total', 10, 2)->default(0);
            $table->unsignedInteger('rounds_played')->default(0);
            $table->unsignedInteger('correct_outcomes_count')->default(0);
            $table->unsignedInteger('exact_scores_count')->default(0);
            $table->unsignedInteger('close_score_count')->default(0);
            $table->decimal('accuracy_percentage', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('refreshed_at')->nullable();
            $table->timestamps();

            $table->index(['leaderboard_type', 'campaign_id'], 'pred_lb_type_cmp_idx');
            $table->index(['leaderboard_type', 'season_id'], 'pred_lb_type_season_idx');
            $table->index(['leaderboard_type', 'round_id'], 'pred_lb_type_round_idx');
            $table->index(['leaderboard_type', 'leaderboard_period_key'], 'pred_lb_type_period_idx');
            $table->unique(['leaderboard_type', 'campaign_id', 'season_id', 'round_id', 'leaderboard_period_key', 'user_id'], 'pred_lb_scope_user_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictor_leaderboard_entries');
    }
};
