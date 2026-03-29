<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('board_type');
            $table->string('period_key');
            $table->string('user_id', 64);
            $table->string('display_name_snapshot')->nullable();
            $table->string('avatar_url_snapshot')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('quizzes_played')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->decimal('accuracy', 5, 2)->default(0);
            $table->decimal('avg_score', 6, 2)->nullable();
            $table->unsignedInteger('rank_position');
            $table->timestamps();

            $table->unique(['board_type', 'period_key', 'user_id'], 'le_board_period_user_unique');
            $table->index(['board_type', 'period_key', 'rank_position'], 'le_board_period_rank_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_entries');
    }
};
