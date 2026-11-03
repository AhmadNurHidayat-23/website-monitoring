<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('url', 2048)->unique();
            $table->unsignedSmallInteger('interval_minutes')->default(5);
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('http_status_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('checked_url', 2048);
            $table->string('status');
            $table->unsignedSmallInteger('http_status_code')->nullable();
            $table->unsignedInteger('response_time_ms');
            $table->string('error')->nullable();
            $table->timestamp('checked_at');
            $table->index(['website_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checks');
        Schema::dropIfExists('websites');
    }
};
