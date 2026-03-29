<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictor_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('display_name');
            $table->string('sponsor_name')->nullable();
            $table->text('description')->nullable();
            $table->string('scope_type', 32)->default('single_competition');
            $table->unsignedInteger('default_fixture_count')->default(4);
            $table->boolean('banker_enabled')->default(true);
            $table->string('status', 32)->default('draft');
            $table->string('visibility', 32)->default('public');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'visibility'], 'pred_cmp_status_vis_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictor_campaigns');
    }
};
