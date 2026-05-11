<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_redirects_guests_to_login(): void
    {
        User::factory()->create();

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
