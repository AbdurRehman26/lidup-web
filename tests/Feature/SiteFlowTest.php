<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Release;
use App\Models\SubscriptionPackage;
use App\Models\TaskCompletionEvent;
use App\Models\User;
use App\Notifications\ActivationKeyIssued;
use App\Notifications\TaskCompleted;
use App\Services\ApiKeyService;
use Database\Seeders\AdminUserSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
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
        $this->assertDatabaseCount('subscription_packages', 4);
    }

    public function test_landing_page_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('auth.user', null)
            );
    }

    public function test_an_eligible_visitor_receives_a_free_trial_when_creating_an_account(): void
    {
        $this->post('/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $user = User::where('email', 'ada@example.com')->firstOrFail();

        $this->assertSame(1, $user->trial_cohort_position);
        $this->assertSame('personal', $user->trial_plan);
        $this->assertTrue($user->onAppTrial());
        $this->assertEqualsWithDelta(now()->addDays(14)->timestamp, $user->trial_ends_at->timestamp, 5);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_users_advance_through_day_month_and_unlimited_package_tiers(): void
    {
        SubscriptionPackage::query()->update(['is_active' => false]);
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
        ])->assertRedirect('/dashboard');

        $this->post('/logout')->assertRedirect('/');

        $this->post('/register', [
            'name' => 'Second User',
            'email' => 'second@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'pro',
        ])->assertRedirect('/dashboard');

        $this->post('/logout')->assertRedirect('/');

        $this->post('/register', [
            'name' => 'Third User',
            'email' => 'third@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ])->assertRedirect('/dashboard');

        $this->post('/logout')->assertRedirect('/');

        $this->post('/register', [
            'name' => 'Fourth User',
            'email' => 'fourth@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ])->assertRedirect('/subscription?plan=personal');

        $first = User::where('email', 'first@example.com')->firstOrFail();
        $second = User::where('email', 'second@example.com')->firstOrFail();
        $third = User::where('email', 'third@example.com')->firstOrFail();

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

        $this->get('/api/documentation')
            ->assertOk()
            ->assertSee('LidUp API');
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

    public function test_download_page_is_public(): void
    {
        $this->get('/download')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Download')
                ->where('release.version', '1.0.0-beta.1')
            );
    }

    public function test_a_user_can_create_a_paddle_subscription_checkout(): void
    {
        $user = User::factory()->create();
        $user->customer()->create([
            'paddle_id' => 'ctm_test_123',
            'name' => $user->name,
            'email' => $user->email,
        ]);
        config([
            'cashier.client_side_token' => 'test_token',
            'plans.personal.paddle_price_id' => 'pri_personal_test',
        ]);

        $this->actingAs($user)
            ->postJson('/subscription/checkout', ['plan' => 'personal'])
            ->assertOk()
            ->assertJsonPath('checkout.items.0.priceId', 'pri_personal_test')
            ->assertJsonPath('checkout.customer.id', 'ctm_test_123')
            ->assertJsonPath('checkout.customData.subscription_type', 'default');
    }

    public function test_a_legacy_subscription_without_a_paddle_id_never_calls_the_swap_api(): void
    {
        $user = User::factory()->create();
        $user->appSubscription()->create([
            'plan' => 'personal',
            'status' => 'trialing',
        ]);
        config(['plans.pro.paddle_price_id' => 'pri_pro_test']);

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

    public function test_registration_generates_a_hashed_activation_key(): void
    {
        $response = $this->post('/register', [
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ]);

        $response->assertRedirect('/dashboard')->assertSessionHas('plain_api_key');
        $plainText = session('plain_api_key');

        $this->assertStringContainsString('|lidup_', $plainText);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => User::where('email', 'grace@example.com')->value('id'),
            'token' => hash('sha256', Str::after($plainText, '|')),
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
}
