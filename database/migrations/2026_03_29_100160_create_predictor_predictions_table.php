<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictor_predictions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('round_entry_id')->constrained('predictor_round_entries')->cascadeOnDelete();
            $table->foreignId('round_fixture_id')->constrained('predictor_round_fixtures')->cascadeOnDelete();
            $table->unsignedTinyInteger('predicted_home_score');
            $table->unsignedTinyInteger('predicted_away_score');
            $table->string('predicted_outcome', 32);
            $table->boolean('is_banker')->default(false);
            $table->boolean('was_submitted')->default(false);
            $table->decimal('points_awarded', 10, 2)->default(0);
            $table->decimal('outcome_points', 10, 2)->default(0);
            $table->decimal('exact_score_points', 10, 2)->default(0);
            $table->decimal('close_score_points', 10, 2)->default(0);
            $table->decimal('banker_bonus_points', 10, 2)->default(0);
            $table->string('scoring_status', 32)->default('pending');
            $table->text('scoring_notes')->nullable();
            $table->dateTime('scored_at')->nullable();
            $table->timestamps();

            $table->unique(['round_entry_id', 'round_fixture_id'], 'pred_pred_entry_fix_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictor_predictions');
    }
};
