<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->timestamp('viewed_at')->nullable()->after('sent_at');
            $table->timestamp('push_notified_at')->nullable()->after('viewed_at');
            $table->index(['user_id', 'analysis_state', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'analysis_state', 'viewed_at']);
            $table->dropColumn(['viewed_at', 'push_notified_at']);
        });
    }
};
