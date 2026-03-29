<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictor_campaign_competitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('predictor_campaigns')->cascadeOnDelete();
            $table->unsignedBigInteger('competition_id')->nullable();
            $table->string('competition_name_snapshot')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['campaign_id', 'competition_id'], 'pred_cmp_comp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictor_campaign_competitions');
    }
};
