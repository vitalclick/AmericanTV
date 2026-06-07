<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Provider-side video UID (Cloudflare Stream / Mux / Bunny).
            $table->string('stream_provider')->nullable()->after('storage_id');
            $table->string('stream_provider_id')->nullable()->after('stream_provider')->index();

            // HLS manifest URL (master playlist). For signed delivery this is a
            // template; signed URLs are minted per request.
            $table->string('hls_manifest_url')->nullable()->after('stream_provider_id');

            // 0 = not started, 1 = uploading, 2 = transcoding, 3 = ready, 4 = failed.
            $table->tinyInteger('hls_status')->default(0)->after('hls_manifest_url');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'stream_provider',
                'stream_provider_id',
                'hls_manifest_url',
                'hls_status',
            ]);
        });
    }
};
