<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Release;
use App\Models\UpdateSubscriber;
use App\Models\User;
use App\Notifications\ActivationKeyIssued;
use App\Services\ApiKeyService;
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

    public function test_landing_page_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('auth.user', null)
            );
    }

    public function test_a_visitor_can_create_an_account_and_continue_to_checkout(): void
    {
        $this->post('/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ])->assertRedirect('/subscription?plan=personal');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
        $this->assertDatabaseCount('subscriptions', 0);
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

    public function test_a_visitor_can_join_the_update_list(): void
    {
        $this->post('/updates', ['email' => 'news@example.com'])
            ->assertRedirect()
            ->assertSessionHas('subscribed');

        $this->assertTrue(UpdateSubscriber::where('email', 'news@example.com')->exists());
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

        $response->assertRedirect('/subscription?plan=personal')->assertSessionHas('plain_api_key');
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
}
