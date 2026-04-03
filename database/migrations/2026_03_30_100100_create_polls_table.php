<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 32)->default('single_choice');
            $table->string('category', 64)->nullable();
            $table->string('title');
            $table->string('question');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('short_description')->nullable();
            $table->string('status', 32)->default('draft');
            $table->string('visibility', 32)->default('public');
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->boolean('login_required')->default(true);
            $table->boolean('verified_account_required')->default(false);
            $table->boolean('allow_result_view_before_vote')->default(false);
            $table->string('result_visibility_mode', 32)->default('hidden_until_end');
            $table->string('context_type', 64)->nullable();
            $table->string('context_id', 128)->nullable();
            $table->string('sponsor_name')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('banner_image_url')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('published_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'visibility'], 'polls_status_vis_idx');
            $table->index(['open_at', 'close_at'], 'polls_open_close_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};
