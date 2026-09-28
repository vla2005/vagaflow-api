<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('automation_profiles', function (Blueprint $table) {
            $table->longText('resume_data')->nullable()->after('resume_text');
            $table->string('resume_parse_status', 20)->default('missing')->after('resume_data');
            $table->text('resume_parse_error')->nullable()->after('resume_parse_status');
            $table->timestamp('resume_parsed_at')->nullable()->after('resume_parse_error');
        });

        DB::table('automation_profiles')
            ->whereNotNull('resume_text')
            ->update([
                'is_active' => false,
                'resume_parse_status' => 'pending',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automation_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'resume_data',
                'resume_parse_status',
                'resume_parse_error',
                'resume_parsed_at',
            ]);
        });
    }
};
