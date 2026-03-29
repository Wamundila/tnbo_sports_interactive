<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trivia_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trivia_quiz_id')->constrained()->cascadeOnDelete();
            $table->string('user_id', 64);
            $table->string('display_name_snapshot')->nullable();
            $table->string('avatar_url_snapshot')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->dateTime('submitted_at')->nullable();
            $table->string('status')->default('in_progress');
            $table->unsignedInteger('score_base')->default(0);
            $table->unsignedInteger('score_bonus')->default(0);
            $table->unsignedInteger('score_total')->default(0);
            $table->unsignedInteger('correct_answers_count')->default(0);
            $table->unsignedInteger('wrong_answers_count')->default(0);
            $table->unsignedInteger('unanswered_count')->default(0);
            $table->unsignedInteger('time_taken_seconds')->nullable();
            $table->unsignedInteger('streak_before')->default(0);
            $table->unsignedInteger('streak_after')->default(0);
            $table->json('ranking_snapshot')->nullable();
            $table->string('client_type')->nullable();
            $table->timestamps();

            $table->unique(['trivia_quiz_id', 'user_id'], 'ta_quiz_user_unique');
            $table->index('user_id');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trivia_attempts');
    }
};
