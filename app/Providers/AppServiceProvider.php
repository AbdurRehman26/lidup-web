<?php

namespace App\Providers;

use App\Listeners\SyncPaddleSubscription;
use App\Models\Subscription;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionPaused;
use Laravel\Paddle\Events\SubscriptionUpdated;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config([
            'l5-swagger.documentations.default.api.title' => 'LidUp API',
            'l5-swagger.documentations.default.paths.use_absolute_path' => false,
            'l5-swagger.defaults.generate_always' => app()->isLocal(),
        ]);

        Cashier::useSubscriptionModel(Subscription::class);

        Event::listen(SubscriptionCreated::class, SyncPaddleSubscription::class);
        Event::listen(SubscriptionUpdated::class, SyncPaddleSubscription::class);
        Event::listen(SubscriptionPaused::class, SyncPaddleSubscription::class);
        Event::listen(SubscriptionCanceled::class, SyncPaddleSubscription::class);
    }
}
