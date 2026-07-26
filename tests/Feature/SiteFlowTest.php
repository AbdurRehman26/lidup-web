<?php

namespace Tests\Feature;

use App\Models\UpdateSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
        $this->assertDatabaseHas('subscriptions', [
            'plan' => 'early-access',
            'status' => 'trialing',
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
}
