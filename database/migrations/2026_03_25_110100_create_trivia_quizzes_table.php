<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trivia_quizzes', function (Blueprint $table): void {
            $table->id();
            $table->date('quiz_date')->unique();
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->unsignedInteger('question_count_expected')->default(3);
            $table->unsignedInteger('time_per_question_seconds')->default(30);
            $table->unsignedInteger('points_per_correct')->default(3);
            $table->boolean('streak_bonus_enabled')->default(true);
            $table->string('sport_slug')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->unsignedBigInteger('published_by_admin_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trivia_quizzes');
    }
};
