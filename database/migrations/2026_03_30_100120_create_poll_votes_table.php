<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained('poll_options')->cascadeOnDelete();
            $table->string('user_id', 64);
            $table->string('display_name_snapshot')->nullable();
            $table->string('avatar_url_snapshot')->nullable();
            $table->string('client', 64)->nullable();
            $table->string('session_id', 128)->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('submitted_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['poll_id', 'user_id'], 'poll_votes_poll_user_unq');
            $table->index(['poll_option_id', 'submitted_at'], 'poll_votes_opt_sub_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
    }
};
