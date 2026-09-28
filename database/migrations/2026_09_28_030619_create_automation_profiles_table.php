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
        Schema::create('automation_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(false);
            $table->json('job_titles')->default('[]');
            $table->json('seniorities')->default('[]');
            $table->json('technologies')->default('[]');
            $table->json('excluded_keywords')->default('[]');
            $table->json('work_modes')->default('[]');
            $table->json('locations')->default('[]');
            $table->unsignedTinyInteger('minimum_score')->default(75);
            $table->longText('resume_text')->nullable();
            $table->timestamp('resume_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_profiles');
    }
};
