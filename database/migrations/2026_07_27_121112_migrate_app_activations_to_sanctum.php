<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_activations', function (Blueprint $table) {
            $table->foreignId('personal_access_token_id')
                ->nullable()
                ->after('user_id')
                ->constrained('personal_access_tokens')
                ->cascadeOnDelete();
        });

        Schema::table('app_activations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('api_key_id');
        });

        Schema::dropIfExists('api_keys');
    }

    public function down(): void
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

        Schema::table('app_activations', function (Blueprint $table) {
            $table->foreignId('api_key_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dropConstrainedForeignId('personal_access_token_id');
        });
    }
};
