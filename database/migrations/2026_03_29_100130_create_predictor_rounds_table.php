<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictor_rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_id')->constrained('predictor_seasons')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('round_number')->nullable();
            $table->dateTime('opens_at');
            $table->dateTime('prediction_closes_at');
            $table->dateTime('round_closes_at');
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('fixture_count')->default(0);
            $table->boolean('allow_partial_submission')->default(false);
            $table->dateTime('leaderboard_frozen_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['season_id', 'status'], 'pred_round_season_stat_idx');
            $table->index(['opens_at', 'prediction_closes_at'], 'pred_round_open_close_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictor_rounds');
    }
};
