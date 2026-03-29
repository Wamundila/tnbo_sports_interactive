<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trivia_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trivia_quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('question_text');
            $table->string('image_url')->nullable();
            $table->text('explanation_text')->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_ref')->nullable();
            $table->string('difficulty')->nullable();
            $table->string('sport_slug')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['trivia_quiz_id', 'position'], 'tq_quiz_pos_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trivia_questions');
    }
};
