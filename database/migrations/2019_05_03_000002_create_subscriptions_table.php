<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (! Schema::hasColumn('subscriptions', 'billable_type')) {
                    $table->nullableMorphs('billable');
                }

                if (! Schema::hasColumn('subscriptions', 'type')) {
                    $table->string('type')->nullable()->index();
                }

                if (! Schema::hasColumn('subscriptions', 'paddle_id')) {
                    $table->string('paddle_id')->nullable()->unique();
                }

                if (! Schema::hasColumn('subscriptions', 'paused_at')) {
                    $table->timestamp('paused_at')->nullable();
                }
            });

            DB::table('subscriptions')
                ->whereNull('billable_type')
                ->whereNotNull('user_id')
                ->update([
                    'billable_type' => User::class,
                    'type' => 'default',
                ]);

            DB::table('subscriptions')
                ->whereNull('billable_id')
                ->whereNotNull('user_id')
                ->update(['billable_id' => DB::raw('user_id')]);

            return;
        }

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('billable');
            $table->string('type')->nullable();
            $table->string('paddle_id')->nullable()->unique();
            $table->string('status');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
