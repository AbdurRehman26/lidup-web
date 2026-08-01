<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->index();
            $table->string('title', 160);
            $table->text('description');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('submitter_name', 120)->nullable();
            $table->string('submitter_email')->nullable();
            $table->string('status', 30)->default('submitted')->index();
            $table->boolean('is_public')->default(false)->index();
            $table->text('admin_response')->nullable();
            $table->timestamps();
        });

        Schema::create('feedback_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['feedback_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_votes');
        Schema::dropIfExists('feedback_items');
    }
};
