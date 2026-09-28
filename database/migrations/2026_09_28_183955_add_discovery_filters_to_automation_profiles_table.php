<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('automation_profiles', function (Blueprint $table) {
            $table->json('areas')->default('[]')->after('job_titles');
            $table->json('platforms')->default('[]')->after('work_modes');
            $table->json('employment_types')->default('[]')->after('platforms');
            $table->boolean('easy_apply_only')->default(false)->after('employment_types');
            $table->boolean('ai_verified_only')->default(false)->after('easy_apply_only');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automation_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'areas',
                'platforms',
                'employment_types',
                'easy_apply_only',
                'ai_verified_only',
            ]);
        });
    }
};
