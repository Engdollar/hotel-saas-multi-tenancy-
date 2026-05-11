<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_filter_notification_center_by_read_state_and_search(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->insertNotification($superAdmin, 'Billing webhook delayed', 'Webhook retries are backing up.', null);
        $this->insertNotification($superAdmin, 'Resolved nightly export', 'Exports completed successfully.', now());

        $response = $this->actingAs($superAdmin)->get(route('admin.notifications.index', [
            'read_state' => 'unread',
            'search' => 'webhook',
        ]));

        $response->assertOk();
        $response->assertSee('Billing webhook delayed');
        $response->assertSee('1 matching');
        $response->assertSee('Unread');
    }

    public function test_super_admin_can_mark_all_notifications_as_read(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->insertNotification($superAdmin, 'Unassigned approvals', 'Procurement approvals are waiting.', null);
        $this->insertNotification($superAdmin, 'New tenant registration', 'A new company is pending review.', null);

        $response = $this->actingAs($superAdmin)->post(route('admin.notifications.read-all'));

        $response->assertRedirect();
        $this->assertSame(0, $superAdmin->fresh()->unreadNotifications()->count());
    }

    protected function createSuperAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);

        $role = Role::withoutGlobalScopes()->firstOrCreate([
            'company_id' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $user->assignRole($role);

        return $user;
    }

    protected function insertNotification(User $user, string $title, string $message, $readAt): void
    {
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\SystemActivityNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => $title,
                'message' => $message,
                'url' => route('admin.dashboard'),
            ], JSON_THROW_ON_ERROR),
            'read_at' => $readAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}