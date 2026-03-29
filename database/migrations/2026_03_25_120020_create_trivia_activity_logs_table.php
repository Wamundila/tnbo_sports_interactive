<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trivia_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_type');
            $table->string('actor_id')->nullable();
            $table->string('event_name');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trivia_activity_logs');
    }
};
