<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('trial_cohort_position')->nullable()->unique()->after('is_admin');
            $table->string('trial_plan')->nullable()->after('trial_cohort_position');
            $table->timestamp('trial_started_at')->nullable()->after('trial_plan');
            $table->timestamp('trial_ends_at')->nullable()->index()->after('trial_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['trial_cohort_position']);
            $table->dropIndex(['trial_ends_at']);
            $table->dropColumn(['trial_cohort_position', 'trial_plan', 'trial_started_at', 'trial_ends_at']);
        });
    }
};
