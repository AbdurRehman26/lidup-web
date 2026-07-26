<?php

namespace Tests\Feature;

use App\Models\Release;
use App\Models\UpdateSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_a_visitor_can_create_an_account_with_a_trial(): void
    {
        $this->post('/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'plan' => 'personal',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
        $this->assertDatabaseHas('subscriptions', [
            'plan' => 'personal',
            'status' => 'trialing',
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'type' => 'trial_started',
        ]);
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

    public function test_a_user_can_change_and_cancel_their_subscription(): void
    {
        $user = User::factory()->create();
        $user->subscription()->create([
            'plan' => 'personal',
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->actingAs($user)
            ->patch('/subscription', ['plan' => 'pro'])
            ->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan' => 'pro',
            'status' => 'trialing',
        ]);

        $this->actingAs($user)
            ->delete('/subscription')
            ->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'canceled',
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $user->subscription->id,
            'type' => 'plan_changed',
        ]);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $user->subscription->id,
            'type' => 'canceled',
        ]);
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

    public function test_expired_trials_are_closed_by_the_scheduled_command(): void
    {
        $user = User::factory()->create();
        $subscription = $user->subscription()->create([
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
}
