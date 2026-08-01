<?php

namespace App\Providers;

use App\Contracts\TransactionalEmailSender;
use App\Listeners\GrantLifetimePurchase;
use App\Listeners\SyncPaddleSubscription;
use App\Models\PersonalAccessToken;
use App\Models\Subscription;
use App\Services\Mail\LaravelTransactionalEmailSender;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionPaused;
use Laravel\Paddle\Events\SubscriptionUpdated;
use Laravel\Paddle\Events\TransactionCompleted;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TransactionalEmailSender::class,
            LaravelTransactionalEmailSender::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config([
            'l5-swagger.documentations.default.api.title' => 'LidUp API',
            'l5-swagger.documentations.default.paths.use_absolute_path' => false,
            'l5-swagger.defaults.generate_always' => true,
            'livewire.temporary_file_upload.rules' => [
                'required',
                'file',
                'max:'.config('uploads.release_max_kb'),
            ],
            'livewire.temporary_file_upload.max_upload_time' => config('uploads.temporary_upload_minutes'),
        ]);

        Cashier::useSubscriptionModel(Subscription::class);
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Event::listen(SubscriptionCreated::class, SyncPaddleSubscription::class);
        Event::listen(SubscriptionUpdated::class, SyncPaddleSubscription::class);
        Event::listen(SubscriptionPaused::class, SyncPaddleSubscription::class);
        Event::listen(SubscriptionCanceled::class, SyncPaddleSubscription::class);
        Event::listen(TransactionCompleted::class, GrantLifetimePurchase::class);
    }
}
