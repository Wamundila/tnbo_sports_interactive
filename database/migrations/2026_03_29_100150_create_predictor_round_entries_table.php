<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictor_round_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('round_id')->constrained('predictor_rounds')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('predictor_campaigns')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('predictor_seasons')->cascadeOnDelete();
            $table->string('user_id', 64);
            $table->string('display_name_snapshot')->nullable();
            $table->string('avatar_url_snapshot')->nullable();
            $table->string('entry_status', 32)->default('draft');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('last_edited_at')->nullable();
            $table->decimal('total_points', 10, 2)->default(0);
            $table->unsignedInteger('correct_outcomes_count')->default(0);
            $table->unsignedInteger('exact_scores_count')->default(0);
            $table->unsignedInteger('close_score_count')->default(0);
            $table->foreignId('banker_fixture_id')->nullable()->constrained('predictor_round_fixtures')->nullOnDelete();
            $table->decimal('banker_multiplier', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['round_id', 'user_id'], 'pred_entry_round_user_unq');
            $table->index(['campaign_id', 'season_id', 'user_id'], 'pred_entry_cmp_user_idx');
            $table->index(['entry_status', 'submitted_at'], 'pred_entry_status_sub_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictor_round_entries');
    }
};
