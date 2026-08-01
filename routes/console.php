<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('users:make-admin {email}', function (string $email) {
    $user = User::query()->where('email', $email)->first();

    if (! $user) {
        $this->error("No user was found with the email address {$email}.");

        return 1;
    }

    $user->forceFill(['is_admin' => true])->save();
    $this->info("{$user->email} can now access the Filament admin panel.");

    return 0;
})->purpose('Grant a user access to the Filament admin panel');

Schedule::command('subscriptions:expire-trials')->hourly();
Schedule::command('horizon:snapshot')->everyFiveMinutes();
