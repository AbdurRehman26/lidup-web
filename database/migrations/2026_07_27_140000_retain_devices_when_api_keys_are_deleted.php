<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_activations', function (Blueprint $table) {
            $table->dropForeign(['personal_access_token_id']);
            $table->foreign('personal_access_token_id')
                ->references('id')
                ->on('personal_access_tokens')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('app_activations', function (Blueprint $table) {
            $table->dropForeign(['personal_access_token_id']);
            $table->foreign('personal_access_token_id')
                ->references('id')
                ->on('personal_access_tokens')
                ->cascadeOnDelete();
        });
    }
};
