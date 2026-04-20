<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictor_campaigns', function (Blueprint $table): void {
            $table->string('banner_image_url')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('predictor_campaigns', function (Blueprint $table): void {
            $table->dropColumn('banner_image_url');
        });
    }
};
