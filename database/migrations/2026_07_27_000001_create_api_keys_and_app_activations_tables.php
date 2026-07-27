<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('LidUp app');
            $table->string('prefix', 20)->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('app_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_key_id')->constrained()->cascadeOnDelete();
            $table->string('device_id');
            $table->string('device_name')->nullable();
            $table->string('app_version')->nullable();
            $table->timestamp('activated_at');
            $table->timestamp('last_verified_at');
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_activations');
        Schema::dropIfExists('api_keys');
    }
};
