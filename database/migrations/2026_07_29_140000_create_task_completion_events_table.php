<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_completion_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_id');
            $table->string('task_id')->nullable();
            $table->string('status', 40);
            $table->text('summary');
            $table->unsignedBigInteger('duration_seconds')->nullable();
            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'event_id']);
            $table->index(['user_id', 'completed_at']);
        });

        DB::table('personal_access_tokens')
            ->orderBy('id')
            ->each(function (object $token): void {
                $abilities = json_decode($token->abilities ?? '[]', true) ?: [];

                if (! in_array('tasks:complete', $abilities, true)) {
                    $abilities[] = 'tasks:complete';

                    DB::table('personal_access_tokens')
                        ->where('id', $token->id)
                        ->update(['abilities' => json_encode(array_values($abilities))]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_completion_events');
    }
};
