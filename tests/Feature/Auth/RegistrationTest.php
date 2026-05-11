<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_companies_can_register_with_admin_user(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Acme Campus',
            'subdomain' => 'acme',
            'custom_domain' => '',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('companies', ['name' => 'Acme Campus', 'status' => Company::STATUS_PENDING]);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $response->assertRedirect(route('tenant.access-status', absolute: false));
    }
}
