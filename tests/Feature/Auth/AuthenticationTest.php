<?php

namespace Tests\Feature\Auth;

use App\Providers\AppServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_local_ip_requests_clear_the_configured_session_domain(): void
    {
        config(['session.domain' => '.hotel-saas.test']);
        $this->app->instance('request', Request::create('http://127.0.0.1/login', 'GET'));

        $provider = new AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'normalizeLocalSessionDomain');
        $method->setAccessible(true);
        $method->invoke($provider);

        $this->assertNull(config('session.domain'));
    }

    public function test_tenant_hosts_keep_the_configured_session_domain(): void
    {
        config(['session.domain' => '.hotel-saas.test']);
        $this->app->instance('request', Request::create('https://demo.hotel-saas.test/login', 'GET'));

        $provider = new AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'normalizeLocalSessionDomain');
        $method->setAccessible(true);
        $method->invoke($provider);

        $this->assertSame('.hotel-saas.test', config('session.domain'));
    }
}
