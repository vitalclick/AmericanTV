<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Captured during FFMpeg transcode (VideoManager::processVideo). The
            // mobile feed surfaces this in VideoSummary; nullable so existing
            // rows aren't blocked on backfill.
            $table->unsignedInteger('duration_seconds')->nullable()->after('hls_status');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('duration_seconds');
        });
    }
};
