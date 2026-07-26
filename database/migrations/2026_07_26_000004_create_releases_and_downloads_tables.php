<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('channel')->default('stable')->index();
            $table->string('platform')->default('macos');
            $table->string('architecture')->default('universal');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->string('minimum_os')->default('macOS 14 Sonoma');
            $table->text('release_notes')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->timestamp('downloaded_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('releases');
    }
};
