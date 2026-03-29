<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictor_round_fixtures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('round_id')->constrained('predictor_rounds')->cascadeOnDelete();
            $table->unsignedBigInteger('source_fixture_id')->nullable();
            $table->unsignedBigInteger('competition_id')->nullable();
            $table->string('competition_name_snapshot')->nullable();
            $table->unsignedBigInteger('home_team_id')->nullable();
            $table->unsignedBigInteger('away_team_id')->nullable();
            $table->string('home_team_name_snapshot');
            $table->string('away_team_name_snapshot');
            $table->string('home_team_logo_url')->nullable();
            $table->string('away_team_logo_url')->nullable();
            $table->dateTime('kickoff_at');
            $table->unsignedInteger('display_order')->default(1);
            $table->string('result_status', 32)->default('pending');
            $table->unsignedTinyInteger('actual_home_score')->nullable();
            $table->unsignedTinyInteger('actual_away_score')->nullable();
            $table->dateTime('result_entered_at')->nullable();
            $table->string('result_source')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['round_id', 'display_order'], 'pred_rfix_round_order_unq');
            $table->index(['round_id', 'kickoff_at'], 'pred_rfix_round_kick_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictor_round_fixtures');
    }
};
