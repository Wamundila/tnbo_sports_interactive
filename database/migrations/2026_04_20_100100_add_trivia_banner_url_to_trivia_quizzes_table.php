<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trivia_quizzes', function (Blueprint $table): void {
            $table->string('trivia_banner_url')->nullable()->after('short_description');
        });
    }

    public function down(): void
    {
        Schema::table('trivia_quizzes', function (Blueprint $table): void {
            $table->dropColumn('trivia_banner_url');
        });
    }
};
