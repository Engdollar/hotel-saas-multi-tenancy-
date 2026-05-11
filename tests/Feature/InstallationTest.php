<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_installer_when_application_has_no_users(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('install.create'));
    }

    public function test_installer_screen_can_be_rendered_on_fresh_application(): void
    {
        $response = $this->get(route('install.create'));

        $response->assertOk();
        $response->assertSee('Platform setup wizard');
        $response->assertSee('Installation workspace');
        $response->assertSee('Open Documentation');
    }

    public function test_documentation_screen_can_be_rendered(): void
    {
        $response = $this->get(route('documentation.index'));

        $response->assertOk();
        $response->assertSee('Documentation');
        $response->assertSee('Project handbook and operational reference.');
    }

    public function test_installer_stays_available_locally_once_application_is_bootstrapped(): void
    {
        User::factory()->create();

        $response = $this->get(route('install.create'));

        $response->assertOk();
        $response->assertSee('Local reinstall mode');
    }

    public function test_installer_database_test_endpoint_returns_success_payload(): void
    {
        $mock = Mockery::mock(\App\Services\InstallerDatabaseService::class);
        $mock->shouldReceive('testMySqlConnection')
            ->once()
            ->andReturn([
                'passes' => true,
                'message' => 'Database connection successful.',
            ]);

        $this->app->instance(\App\Services\InstallerDatabaseService::class, $mock);

        $response = $this->postJson(route('install.test-database'), [
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_database' => 'eelo_test',
            'db_username' => 'root',
            'db_password' => '',
        ]);

        $response->assertOk();
        $response->assertJson([
            'passes' => true,
            'message' => 'Database connection successful.',
        ]);
    }
}