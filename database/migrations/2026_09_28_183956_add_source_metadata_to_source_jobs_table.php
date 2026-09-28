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
        Schema::table('source_jobs', function (Blueprint $table) {
            $table->string('area', 40)->nullable()->after('level');
            $table->string('platform', 40)->nullable()->after('work_mode');
            $table->string('employment_type', 40)->nullable()->after('platform');
            $table->boolean('is_easy_apply')->default(false)->after('employment_type');
            $table->boolean('is_ai_verified')->default(false)->after('is_easy_apply');
            $table->boolean('is_closed')->default(false)->after('is_ai_verified');

            $table->index(['area', 'level', 'last_seen_at']);
            $table->index(['platform', 'last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('source_jobs', function (Blueprint $table) {
            $table->dropIndex(['area', 'level', 'last_seen_at']);
            $table->dropIndex(['platform', 'last_seen_at']);
            $table->dropColumn([
                'area',
                'platform',
                'employment_type',
                'is_easy_apply',
                'is_ai_verified',
                'is_closed',
            ]);
        });
    }
};
