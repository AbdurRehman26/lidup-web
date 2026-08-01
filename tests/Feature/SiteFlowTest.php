<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Listeners\GrantLifetimePurchase;
use App\Models\FeedbackItem;
use App\Models\Release;
use App\Models\SubscriptionPackage;
use App\Models\TaskCompletionEvent;
use App\Models\User;
use App\Notifications\ActivationKeyIssued;
use App\Notifications\TaskCompleted;
use App\Services\ApiKeyService;
use Database\Seeders\AdminUserSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Paddle\Events\TransactionCompleted;
use Laravel\Paddle\Transaction;
use Laravel\Telescope\Telescope;
use Livewire\Livewire;
use Tests\TestCase;

class SiteFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_default_admin_seeder_assigns_one_key_and_unlimited_access_without_duplicates(): void
    {
        config([
            'admin.default_user.name' => 'Syed Abdur Rehman',
            'admin.default_user.email' => 'sydabdrehman@gmail.com',
            'admin.default_user.password' => 'sydabdrehman@gmail.com',
        ]);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $user = User::where('email', 'sydabdrehman@gmail.com')->firstOrFail();

        $this->assertTrue($user->is_admin);
        $this->assertSame('admin-unlimited', $user->subscriptionPackage->slug);
        $this->assertSame('unlimited', $user->subscriptionPackage->duration_unit);
        $this->assertNull($user->trial_ends_at);
        $this->assertTrue($user->onAppTrial());
        $this->assertSame(1, $user->tokens()->count());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('subscription_packages', 8);
    }

    public function test_landing_page_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('property="og:image"', false)
            ->assertSee('/marketing/social-share.jpg', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('auth.user', null)
                ->where('trialOffer.user_limit', 10)
                ->where('trialOffer.users_count', 0)
                ->where('trialOffer.remaining_spots', 10)
            );

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Register')
                ->where('trialOffer.remaining_spots', 10)
                ->missing('plans')
            );
    }

    public function test_paid_packages_remain_stored_but_are_hidden_during_early_access(): void
    {
        SubscriptionPackage::where('slug', 'personal')->update([
            'price' => 12.50,
            'currency' => 'GBP',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('paidPlans')
                ->has('packages', 2)
            );

        $this->actingAs(User::factory()->create())
            ->get('/subscription')
            ->assertRedirect('/dashboard');

        $personal = SubscriptionPackage::where('slug', 'personal')->firstOrFail();
        $this->assertSame('12.50', $personal->price);
        $this->assertSame('GBP', $personal->currency);
        $this->assertFalse($personal->is_visible);
    }

    public function test_a_completed_one_time_paddle_transaction_grants_lifetime_access(): void
    {
        $package = SubscriptionPackage::where('slug', 'personal')->firstOrFail();
        $package->update(['paddle_price_id' => 'pri_lifetime_personal']);
        $user = User::factory()->create();
        $event = new TransactionCompleted($user, new Transaction, [
            'data' => [
                'items' => [['price' => ['id' => 'pri_lifetime_personal']]],
            ],
        ]);

        app(GrantLifetimePurchase::class)->handle($event);

        $user->refresh();
        $this->assertSame($package->id, $user->subscription_package_id);
        $this->assertNull($user->trial_ends_at);
        $this->assertTrue($user->onAppTrial());
    }

    public function test_an_eligible_visitor_receives_a_free_trial_when_creating_an_account(): void
    {
        $this->post('/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ])->assertRedirect('/email/verify');

        $this->assertAuthenticated();
        $user = User::where('email', 'ada@example.com')->firstOrFail();

        $this->assertNull($user->trial_cohort_position);
        $this->assertSame('personal', $user->trial_plan);
        $this->assertFalse($user->onAppTrial());
        $this->assertNull($user->trial_ends_at);
        $this->assertDatabaseCount('subscriptions', 0);

        $user->markEmailAsVerified();
        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
        $this->assertSame(0, $user->tokens()->count());

        $this->post('/api-key')->assertRedirect();
        $user->refresh();
        $this->assertSame(1, $user->trial_cohort_position);
        $this->assertTrue($user->onAppTrial());
        $this->assertEqualsWithDelta(now()->addMonthNoOverflow()->timestamp, $user->trial_ends_at->timestamp, 5);

        $this->actingAs($user->fresh())->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('trial.cohort_position', 1)
                ->where('trial.package.user_limit', 10)
                ->where('trial.package.users_count', 1)
                ->where('trial.package.remaining_spots', 9)
            );
    }

    public function test_users_advance_through_day_month_and_unlimited_package_tiers(): void
    {
        SubscriptionPackage::query()->where('is_paid', false)->update(['is_active' => false]);
        $dayTier = SubscriptionPackage::create([
            'name' => 'Day tier',
            'slug' => 'test-day-tier',
            'plan' => 'personal',
            'device_limit' => 1,
            'user_limit' => 1,
            'duration_unit' => 'days',
            'duration_value' => 7,
            'is_paid' => false,
            'is_active' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);
        $monthTier = SubscriptionPackage::create([
            'name' => 'Month tier',
            'slug' => 'test-month-tier',
            'plan' => 'pro',
            'device_limit' => 3,
            'user_limit' => 1,
            'duration_unit' => 'months',
            'duration_value' => 2,
            'is_paid' => false,
            'is_active' => true,
            'is_visible' => true,
            'sort_order' => 2,
        ]);
        $unlimitedTier = SubscriptionPackage::create([
            'name' => 'Unlimited tier',
            'slug' => 'test-unlimited-tier',
            'plan' => 'personal',
            'device_limit' => 1,
            'user_limit' => 1,
            'duration_unit' => 'unlimited',
            'is_paid' => false,
            'is_active' => true,
            'is_visible' => false,
            'sort_order' => 3,
        ]);

        $this->post('/register', [
            'name' => 'First User',
            'email' => 'first@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ])->assertRedirect('/email/verify');

        $first = User::where('email', 'first@example.com')->firstOrFail();
        $first->markEmailAsVerified();
        $this->actingAs($first->fresh())->post('/api-key')->assertRedirect();
        $this->post('/logout')->assertRedirect('/');

        $this->post('/register', [
            'name' => 'Second User',
            'email' => 'second@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'pro',
        ])->assertRedirect('/email/verify');

        $second = User::where('email', 'second@example.com')->firstOrFail();
        $second->markEmailAsVerified();
        $this->actingAs($second->fresh())->post('/api-key')->assertRedirect();
        $this->post('/logout')->assertRedirect('/');

        $this->post('/register', [
            'name' => 'Third User',
            'email' => 'third@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ])->assertRedirect('/email/verify');

        $third = User::where('email', 'third@example.com')->firstOrFail();
        $third->markEmailAsVerified();
        $this->actingAs($third->fresh())->post('/api-key')->assertRedirect();
        $this->post('/logout')->assertRedirect('/');

        $this->post('/register', [
            'name' => 'Fourth User',
            'email' => 'fourth@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ])->assertRedirect('/email/verify');

        $fourth = User::where('email', 'fourth@example.com')->firstOrFail();
        $fourth->markEmailAsVerified();
        $this->actingAs($fourth->fresh())->post('/api-key')->assertRedirect();

        $first->refresh();
        $second->refresh();
        $third->refresh();
        $this->assertSame($dayTier->id, $first->subscription_package_id);
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $first->trial_ends_at->timestamp, 5);
        $this->assertSame($monthTier->id, $second->subscription_package_id);
        $this->assertEqualsWithDelta(now()->addMonthsNoOverflow(2)->timestamp, $second->trial_ends_at->timestamp, 5);
        $this->assertSame($unlimitedTier->id, $third->subscription_package_id);
        $this->assertNull($third->trial_ends_at);
        $this->assertTrue($third->onAppTrial());
        $this->assertNull(User::where('email', 'fourth@example.com')->firstOrFail()->subscription_package_id);
    }

    public function test_a_user_can_log_in_and_view_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('auth.user.email', $user->email)
                ->has('updates')
            );
    }

    public function test_dashboard_exposes_an_assigned_hidden_unlimited_package(): void
    {
        $package = SubscriptionPackage::where('slug', 'admin-unlimited')->firstOrFail();
        $user = User::factory()->create([
            'subscription_package_id' => $package->id,
            'trial_plan' => null,
            'trial_started_at' => null,
            'trial_ends_at' => null,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('trial.active', true)
                ->where('trial.plan', 'pro')
                ->where('trial.package.name', 'Admin Unlimited')
                ->where('trial.package.duration', 'Unlimited')
                ->where('trial.package.is_active', false)
                ->where('trial.package.is_visible', false)
                ->where('trial.ends_at', null)
            );
    }

    public function test_the_faq_page_is_public(): void
    {
        $this->get('/faqs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Faqs'));

        $this->post('/updates')->assertNotFound();
    }

    public function test_swagger_documentation_describes_the_public_api(): void
    {
        $this->artisan('l5-swagger:generate')->assertSuccessful();

        $document = json_decode(
            file_get_contents(storage_path('api-docs/api-docs.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('LidUp API', $document['info']['title']);
        $this->assertSame('/api/v1', $document['servers'][0]['url']);
        $this->assertArrayHasKey('licenseKey', $document['components']['securitySchemes']);
        $this->assertArrayHasKey('/license/validate', $document['paths']);
        $this->assertArrayHasKey('/activation/verify', $document['paths']);
        $this->assertArrayHasKey('/activation/{deviceId}', $document['paths']);
        $this->assertArrayHasKey('/webhooks/task-completed', $document['paths']);
        $this->assertArrayHasKey('/releases/latest', $document['paths']);
        $this->assertArrayHasKey('get', $document['paths']['/releases/latest']);
        $this->assertArrayNotHasKey('security', $document['paths']['/releases/latest']['get']);
        $this->assertSame(
            'uri',
            $document['paths']['/releases/latest']['get']['responses']['200']['content']['application/json']['schema']['properties']['download_url']['format'],
        );

        $this->get('/api/documentation')
            ->assertOk()
            ->assertSee('LidUp API');
    }

    public function test_swagger_json_is_generated_when_the_server_file_is_missing(): void
    {
        File::delete(storage_path('api-docs/api-docs.json'));

        $this->get('/docs?api-docs.json')
            ->assertOk()
            ->assertJsonPath('info.title', 'LidUp API');

        $this->assertFileExists(storage_path('api-docs/api-docs.json'));
    }

    public function test_a_task_completion_webhook_emails_the_user_only_once(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $plainText = app(ApiKeyService::class)->create($user)['plain_text'];
        $payload = [
            'event_id' => 'evt_task_finished_123',
            'task_id' => 'codex-task-184',
            'status' => 'completed',
            'summary' => 'Production build and test suite completed successfully.',
            'duration_seconds' => 754,
            'device_id' => 'work-macbook',
            'device_name' => 'Work MacBook Pro',
            'completed_at' => now()->toIso8601String(),
            'details' => [
                'agent' => 'Codex',
                'tests' => 25,
            ],
        ];

        $this->withToken($plainText)
            ->postJson('/api/v1/webhooks/task-completed', $payload)
            ->assertAccepted()
            ->assertJson([
                'accepted' => true,
                'duplicate' => false,
                'event_id' => 'evt_task_finished_123',
            ]);

        $this->withToken($plainText)
            ->postJson('/api/v1/webhooks/task-completed', $payload)
            ->assertOk()
            ->assertJson([
                'accepted' => true,
                'duplicate' => true,
            ]);

        $event = TaskCompletionEvent::where('event_id', 'evt_task_finished_123')->firstOrFail();

        $this->assertSame($user->id, $event->user_id);
        $this->assertSame(754, $event->duration_seconds);
        $this->assertSame('Codex', $event->details['agent']);
        $this->assertNotNull($event->notification_sent_at);
        Notification::assertSentToTimes($user, TaskCompleted::class, 1);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_telescope_dashboard_is_dark_and_restricted_to_filament_administrators(): void
    {
        $request = Request::create('/telescope');
        $this->assertFalse(Telescope::check($request));

        $user = User::factory()->create();
        $request->setUserResolver(fn () => $user);
        $this->assertFalse(Telescope::check($request));

        $admin = User::factory()->create(['is_admin' => true]);
        $request->setUserResolver(fn () => $admin);
        $this->assertTrue(Telescope::check($request));
        $this->assertTrue(Telescope::$useDarkTheme);
    }

    public function test_dashboard_requires_a_verified_email_address(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/email/verify');

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VerifyEmail')
                ->where('email', $user->email)
            );
    }

    public function test_download_page_is_public(): void
    {
        $this->get('/download')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Download')
                ->where('release.version', '1.0.0-beta.1')
            );
    }

    public function test_hidden_lifetime_packages_cannot_open_checkout(): void
    {
        $user = User::factory()->create();
        $user->customer()->create([
            'paddle_id' => 'ctm_test_123',
            'name' => $user->name,
            'email' => $user->email,
        ]);
        config(['cashier.client_side_token' => 'test_token']);
        SubscriptionPackage::where('slug', 'personal')->update(['paddle_price_id' => 'pri_personal_test']);

        $this->actingAs($user)
            ->postJson('/subscription/checkout', ['plan' => 'personal'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('plan');
    }

    public function test_a_legacy_subscription_without_a_paddle_id_never_calls_the_swap_api(): void
    {
        $user = User::factory()->create();
        $user->appSubscription()->create([
            'plan' => 'personal',
            'status' => 'trialing',
        ]);
        SubscriptionPackage::where('slug', 'pro')->update(['paddle_price_id' => 'pri_pro_test']);

        $this->actingAs($user)
            ->patch('/subscription', ['plan' => 'pro'])
            ->assertRedirect()
            ->assertSessionHasErrors('plan');
    }

    public function test_the_current_release_can_be_downloaded_without_an_account(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('releases/LidUp.dmg', 'signed-app-binary');

        $release = Release::create([
            'version' => '1.0.0',
            'channel' => 'stable',
            'file_path' => 'releases/LidUp.dmg',
            'file_size' => 17,
            'is_current' => true,
            'published_at' => now(),
        ]);

        $this->get('/download/latest')
            ->assertOk()
            ->assertDownload('LidUp-1.0.0.dmg');

        $this->assertGuest();
        $this->assertDatabaseHas('downloads', [
            'release_id' => $release->id,
            'user_id' => null,
        ]);
    }

    public function test_latest_release_metadata_is_public_for_app_update_checks(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('releases/LidUp-1.2.0.dmg', 'signed-release');

        Release::create([
            'version' => '1.2.0',
            'file_path' => 'releases/LidUp-1.2.0.dmg',
            'minimum_os' => 'macOS 14 Sonoma',
            'release_notes' => 'Update checking and reliability improvements.',
            'is_current' => true,
            'published_at' => now(),
        ]);

        $this->getJson('/api/v1/releases/latest')
            ->assertOk()
            ->assertJsonPath('version', '1.2.0')
            ->assertJsonPath('minimum_os', 'macOS 14 Sonoma')
            ->assertJsonPath('available', true)
            ->assertJsonPath('download_url', route('download.latest'));

        $this->assertGuest();
    }

    public function test_uploading_a_latest_build_calculates_metadata_and_replaces_the_previous_latest_build(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('releases/LidUp-1.0.0.dmg', 'first-build');
        Storage::disk('local')->put('releases/LidUp-1.1.0.dmg', 'new-signed-build');

        $previous = Release::create([
            'version' => '1.0.0',
            'file_path' => 'releases/LidUp-1.0.0.dmg',
            'is_current' => true,
            'published_at' => now(),
        ]);

        $latest = Release::create([
            'version' => '1.1.0',
            'file_path' => 'releases/LidUp-1.1.0.dmg',
            'is_current' => true,
            'published_at' => now(),
        ]);

        $this->assertFalse($previous->fresh()->is_current);
        $this->assertTrue($latest->is_current);
        $this->assertSame(strlen('new-signed-build'), $latest->file_size);
        $this->assertSame(hash('sha256', 'new-signed-build'), $latest->sha256);
    }

    public function test_an_authenticated_user_can_open_the_build_upload_form(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get('/admin/releases/create')
            ->assertOk()
            ->assertSee('macOS installer')
            ->assertSee('Make this the latest build');
    }

    public function test_release_upload_and_livewire_temporary_limits_are_aligned(): void
    {
        $this->assertSame(524288, config('uploads.release_max_kb'));
        $this->assertContains('max:524288', config('livewire.temporary_file_upload.rules'));
        $this->assertSame(30, config('livewire.temporary_file_upload.max_upload_time'));
        $this->assertContains('application/zlib', config('uploads.release_mime_types'));
        $this->assertContains('dmg', config('uploads.release_extensions'));
    }

    public function test_a_super_admin_can_view_paid_subscriptions(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $subscriber = User::factory()->create([
            'name' => 'Paid Subscriber',
            'email' => 'paid@example.com',
        ]);
        $subscriber->appSubscription()->create([
            'paddle_id' => 'sub_admin_listing',
            'plan' => 'personal',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/admin/subscriptions')
            ->assertOk()
            ->assertSee('Paid Subscriber')
            ->assertSee('paid@example.com')
            ->assertSee('sub_admin_listing');
    }

    public function test_an_admin_can_generate_and_email_an_activation_key_from_the_users_list(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $user = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$user])
            ->callAction(TestAction::make('generateActivationKey')->table($user))
            ->assertNotified();

        $this->assertTrue($user->tokens()->exists());
        Notification::assertSentTo(
            $user,
            ActivationKeyIssued::class,
            fn (ActivationKeyIssued $notification): bool => str_contains($notification->plainTextKey, '|lidup_')
                && ! $notification->replaced,
        );
    }

    public function test_an_admin_can_view_devices_and_device_history_survives_key_rotation(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $user = User::factory()->create([
            'name' => 'Device Owner',
            'email' => 'owner@example.com',
        ]);
        $createdKey = app(ApiKeyService::class)->create($user);
        $activation = $user->appActivations()->create([
            'personal_access_token_id' => $createdKey['key']->id,
            'device_id' => 'macbook-pro-serial',
            'device_name' => 'Studio MacBook Pro',
            'app_version' => '1.2.0',
            'activated_at' => now(),
            'last_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/devices')
            ->assertOk()
            ->assertSee('Studio MacBook Pro')
            ->assertSee('owner@example.com')
            ->assertSee('1.2.0');

        app(ApiKeyService::class)->rotate($user);

        $this->assertDatabaseHas('app_activations', [
            'id' => $activation->id,
            'personal_access_token_id' => null,
        ]);
        $this->assertNotNull($activation->fresh()->revoked_at);
    }

    public function test_expired_trials_are_closed_by_the_scheduled_command(): void
    {
        $user = User::factory()->create();
        $subscription = $user->appSubscription()->create([
            'plan' => 'personal',
            'status' => 'trialing',
            'trial_ends_at' => now()->subMinute(),
        ]);

        $this->artisan('subscriptions:expire-trials')->assertSuccessful();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'expired',
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'type' => 'trial_expired',
        ]);
    }

    public function test_verifying_email_unlocks_the_dashboard_and_generates_a_hashed_activation_key(): void
    {
        Notification::fake();
        $response = $this->post('/register', [
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ]);

        $response->assertRedirect('/email/verify');
        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertSame(0, $user->tokens()->count());
        Notification::assertSentTo($user, VerifyEmail::class);

        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);
        $this->get($verificationUrl)->assertRedirect('/dashboard');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $dashboard = $this->get('/dashboard');
        $dashboard->assertOk()->assertSessionMissing('plain_api_key');
        $this->assertSame(0, $user->tokens()->count());

        $this->post('/api-key')->assertRedirect()->assertSessionHas('plain_api_key');
        $plainText = session('plain_api_key');
        $token = Str::after($plainText, '|');

        $this->assertStringContainsString('|lidup_', $plainText);
        $this->assertSame(38, strlen($token));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'token' => hash('sha256', $token),
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', ['token' => $plainText]);
    }

    public function test_an_activation_key_verifies_a_device_and_enforces_the_plan_limit(): void
    {
        $user = User::factory()->create();
        $user->appSubscription()->create([
            'paddle_id' => 'sub_personal_test',
            'plan' => 'personal',
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
        ]);
        $plainText = app(ApiKeyService::class)->create($user)['plain_text'];

        $this->withToken($plainText)
            ->postJson('/api/v1/activation/verify', [
                'device_id' => 'macbook-primary',
                'device_name' => 'Main MacBook',
                'app_version' => '1.0.0',
            ])
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'plan' => 'personal',
                'device_limit' => 1,
                'active_devices' => 1,
            ]);

        $this->withToken($plainText)
            ->postJson('/api/v1/activation/verify', [
                'device_id' => 'macbook-second',
            ])
            ->assertConflict()
            ->assertJson([
                'valid' => false,
                'reason' => 'device_limit_reached',
            ]);
    }

    public function test_rotating_a_key_revokes_the_old_key_and_devices(): void
    {
        $user = User::factory()->create();
        $user->appSubscription()->create([
            'paddle_id' => 'sub_pro_test',
            'plan' => 'pro',
            'status' => 'active',
        ]);
        $oldKey = app(ApiKeyService::class)->create($user)['plain_text'];

        $this->actingAs($user)
            ->put('/api-key')
            ->assertRedirect()
            ->assertSessionHas('plain_api_key');

        $newKey = session('plain_api_key');

        $this->withToken($oldKey)
            ->postJson('/api/v1/activation/verify', ['device_id' => 'old-device'])
            ->assertUnauthorized();

        $this->withToken($newKey)
            ->postJson('/api/v1/activation/verify', ['device_id' => 'new-device'])
            ->assertOk();
    }

    public function test_an_activation_key_can_validate_a_paid_license_without_activating_a_device(): void
    {
        $user = User::factory()->create();
        $user->appSubscription()->create([
            'paddle_id' => 'sub_license_test',
            'plan' => 'personal',
            'status' => 'active',
        ]);
        $plainText = app(ApiKeyService::class)->create($user)['plain_text'];

        $this->withToken($plainText)
            ->getJson('/api/v1/license/validate')
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'reason' => null,
                'plan' => 'personal',
                'subscription_status' => 'active',
                'device_limit' => 1,
                'active_devices' => 0,
            ]);

        $this->assertDatabaseCount('app_activations', 0);
    }

    public function test_an_activation_key_can_validate_an_active_account_trial(): void
    {
        $user = User::factory()->create([
            'trial_cohort_position' => 1,
            'trial_plan' => 'pro',
            'trial_started_at' => now()->subDay(),
            'trial_ends_at' => now()->addDays(6),
        ]);
        $plainText = app(ApiKeyService::class)->create($user)['plain_text'];

        $this->withToken($plainText)
            ->getJson('/api/v1/license/validate')
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'plan' => 'pro',
                'subscription_status' => 'trialing',
                'entitlement_source' => 'trial',
                'device_limit' => 3,
            ]);
    }

    public function test_license_validation_rejects_an_expired_account_trial(): void
    {
        $user = User::factory()->create([
            'trial_cohort_position' => 1,
            'trial_plan' => 'personal',
            'trial_started_at' => now()->subDays(8),
            'trial_ends_at' => now()->subDay(),
        ]);
        $plainText = app(ApiKeyService::class)->create($user)['plain_text'];

        $this->withToken($plainText)
            ->getJson('/api/v1/license/validate')
            ->assertForbidden()
            ->assertJson([
                'valid' => false,
                'reason' => 'subscription_inactive',
                'subscription_status' => 'trial_expired',
            ]);
    }

    public function test_license_validation_rejects_invalid_and_unsubscribed_keys(): void
    {
        $this->withToken('invalid-license-key')
            ->getJson('/api/v1/license/validate')
            ->assertUnauthorized();

        $user = User::factory()->create();
        $plainText = app(ApiKeyService::class)->create($user)['plain_text'];

        $this->withToken($plainText)
            ->postJson('/api/v1/license/validate')
            ->assertForbidden()
            ->assertJson([
                'valid' => false,
                'reason' => 'subscription_inactive',
                'subscription_status' => 'inactive',
            ]);
    }

    public function test_guests_can_submit_feedback_for_moderation(): void
    {
        $this->get('/roadmap')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Roadmap'));

        $this->post('/roadmap', [
            'type' => 'feature',
            'title' => 'Add a focus timer',
            'description' => 'Show how long the Mac has stayed awake.',
            'submitter_name' => 'Ada',
            'submitter_email' => 'ada@example.com',
        ])->assertRedirect()->assertSessionHas('feedback_message');

        $this->assertDatabaseHas('feedback_items', [
            'title' => 'Add a focus timer',
            'status' => 'submitted',
            'is_public' => false,
        ]);
    }

    public function test_verified_users_can_vote_for_public_roadmap_items(): void
    {
        $user = User::factory()->create();
        $item = FeedbackItem::create([
            'type' => 'feature',
            'title' => 'Menu bar history',
            'description' => 'Keep a short local history.',
            'status' => 'planned',
            'is_public' => true,
        ]);

        $this->actingAs($user)->post("/roadmap/{$item->id}/vote")->assertRedirect();
        $this->assertDatabaseHas('feedback_votes', ['feedback_item_id' => $item->id, 'user_id' => $user->id]);

        $this->actingAs($user)->post("/roadmap/{$item->id}/vote")->assertRedirect();
        $this->assertDatabaseMissing('feedback_votes', ['feedback_item_id' => $item->id, 'user_id' => $user->id]);
    }

    public function test_unapproved_feedback_is_only_visible_to_its_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = FeedbackItem::create([
            'user_id' => $owner->id,
            'type' => 'feature',
            'title' => 'Private pending idea',
            'description' => 'This has not been approved yet.',
            'status' => 'submitted',
            'is_public' => false,
        ]);

        $this->get('/roadmap')
            ->assertInertia(fn (Assert $page) => $page->where('items', []));

        $this->actingAs($otherUser)->get('/roadmap')
            ->assertInertia(fn (Assert $page) => $page->where('items', []));

        $this->actingAs($owner)->get('/roadmap')
            ->assertInertia(fn (Assert $page) => $page
                ->where('items.0.id', $item->id)
                ->where('items.0.is_public', false)
                ->where('items.0.is_own', true)
                ->where('items.0.status', 'submitted'));
    }

    public function test_dashboard_can_show_the_users_encrypted_activation_key(): void
    {
        $user = User::factory()->create();
        $plainText = app(ApiKeyService::class)->create($user)['plain_text'];
        $stored = DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->value('display_token');

        $this->assertNotSame($plainText, $stored);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('apiKey.plain_text', $plainText));
    }
}
