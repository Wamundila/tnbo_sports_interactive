<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trivia_attempt_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trivia_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trivia_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trivia_question_option_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();

            $table->unique(['trivia_attempt_id', 'trivia_question_id'], 'taa_attempt_question_unique');
            $table->index('trivia_question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trivia_attempt_answers');
    }
};
