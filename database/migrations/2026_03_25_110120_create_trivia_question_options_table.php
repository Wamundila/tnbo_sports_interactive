<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trivia_question_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trivia_question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['trivia_question_id', 'position'], 'tqo_question_pos_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trivia_question_options');
    }
};
