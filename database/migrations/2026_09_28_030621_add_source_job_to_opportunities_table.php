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
        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreignId('source_job_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
            $table->unique(['user_id', 'source_job_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'source_job_id']);
            $table->dropConstrainedForeignId('source_job_id');
        });
    }
};
