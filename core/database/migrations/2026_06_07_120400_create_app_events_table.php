<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name', 64)->index();
            $table->string('platform', 16)->nullable();   // ios / android
            $table->string('session_id', 64)->nullable()->index();
            $table->unsignedBigInteger('video_id')->nullable()->index();
            $table->json('payload')->nullable();          // remaining context.
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_events');
    }
};
