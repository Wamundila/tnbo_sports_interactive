<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_trivia_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('user_id', 64)->unique();
            $table->string('display_name_snapshot')->nullable();
            $table->string('avatar_url_snapshot')->nullable();
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('best_streak')->default(0);
            $table->unsignedInteger('total_points')->default(0);
            $table->unsignedInteger('total_correct_answers')->default(0);
            $table->unsignedInteger('total_wrong_answers')->default(0);
            $table->unsignedInteger('total_quizzes_played')->default(0);
            $table->unsignedInteger('total_quizzes_completed')->default(0);
            $table->decimal('lifetime_accuracy', 5, 2)->default(0);
            $table->date('last_played_quiz_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_trivia_profiles');
    }
};
