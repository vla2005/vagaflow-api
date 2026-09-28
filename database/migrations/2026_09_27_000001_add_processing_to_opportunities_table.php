<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('analysis_state', 20)->default('accepted');
            $table->timestamp('sent_at')->nullable();
            $table->string('resume_path')->nullable();
            $table->index(['user_id', 'analysis_state', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'analysis_state', 'sent_at']);
            $table->dropColumn(['analysis_state', 'sent_at', 'resume_path']);
        });
    }
};
