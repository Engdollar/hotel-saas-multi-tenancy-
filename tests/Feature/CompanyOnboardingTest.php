<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CompanyOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_open_settings_route(): void
    {
        $company = Company::query()->create(['name' => 'Acme', 'status' => 'active']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $permission = Permission::withoutGlobalScopes()->create([
            'company_id' => null,
            'name' => 'read-setting',
            'guard_name' => 'web',
        ]);
        $updatePermission = Permission::withoutGlobalScopes()->create([
            'company_id' => null,
            'name' => 'update-setting',
            'guard_name' => 'web',
        ]);
        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'guard_name' => 'web',
            'is_locked' => true,
        ]);
        $role->syncPermissions([$permission, $updatePermission]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('Settings');
    }

    public function test_pending_tenant_user_is_redirected_to_access_status_page(): void
    {
        $company = Company::query()->create(['name' => 'Acme', 'status' => Company::STATUS_PENDING]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertRedirect(route('tenant.access-status'));

        $statusPage = $this->actingAs($user)->get(route('tenant.access-status'));
        $statusPage->assertOk();
        $statusPage->assertSee('pending approval', false);
    }

    public function test_inactive_tenant_user_is_redirected_to_access_status_page(): void
    {
        $company = Company::query()->create(['name' => 'Acme', 'status' => Company::STATUS_INACTIVE]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertRedirect(route('tenant.access-status'));

        $statusPage = $this->actingAs($user)->get(route('tenant.access-status'));
        $statusPage->assertOk();
        $statusPage->assertSee('inactive', false);
    }

    public function test_company_admin_can_update_tenant_settings(): void
    {
        $company = Company::query()->create(['name' => 'Acme', 'status' => 'active']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $permission = Permission::withoutGlobalScopes()->create([
            'company_id' => null,
            'name' => 'read-setting',
            'guard_name' => 'web',
        ]);
        $updatePermission = Permission::withoutGlobalScopes()->create([
            'company_id' => null,
            'name' => 'update-setting',
            'guard_name' => 'web',
        ]);
        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'guard_name' => 'web',
            'is_locked' => true,
        ]);
        $role->syncPermissions([$permission, $updatePermission]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->put(route('admin.settings.update'), [
            'project_title' => 'Acme Workspace',
            'theme_preset' => 'cleopatra',
            'theme_mode' => 'dark',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertDatabaseHas('settings', [
            'company_id' => $company->id,
            'key' => 'project_title',
            'value' => 'Acme Workspace',
        ]);
    }

    public function test_locked_company_role_cannot_be_edited(): void
    {
        $company = Company::query()->create(['name' => 'Acme', 'status' => 'active']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $permissions = collect(['read-role', 'update-role', 'edit-role'])
            ->map(fn (string $name) => Permission::withoutGlobalScopes()->create([
                'company_id' => null,
                'name' => $name,
                'guard_name' => 'web',
            ]));

        $adminRole = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'guard_name' => 'web',
            'is_locked' => true,
        ]);
        $adminRole->syncPermissions($permissions);
        $user->assignRole($adminRole);

        $response = $this->actingAs($user)->get(route('admin.roles.edit', $adminRole));

        $response->assertForbidden();
    }

    public function test_locked_company_role_assignment_is_preserved_on_user_update(): void
    {
        $company = Company::query()->create(['name' => 'Acme', 'status' => 'active']);
        $actor = User::factory()->create(['company_id' => $company->id]);
        $target = User::factory()->create(['company_id' => $company->id]);

        $permissions = collect(['update-user', 'edit-user'])
            ->map(fn (string $name) => Permission::withoutGlobalScopes()->create([
                'company_id' => null,
                'name' => $name,
                'guard_name' => 'web',
            ]));

        $adminRole = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'guard_name' => 'web',
            'is_locked' => true,
        ]);
        $adminRole->syncPermissions($permissions);

        $staffRole = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Staff',
            'guard_name' => 'web',
            'is_locked' => false,
        ]);

        $actor->assignRole($adminRole);
        $target->assignRole($adminRole);
        $target->assignRole($staffRole);

        $response = $this->actingAs($actor)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => [],
        ]);

        $response->assertRedirect(route('admin.users.edit', $target));
        $this->assertTrue($target->fresh()->hasRole('Admin'));
        $this->assertFalse($target->fresh()->hasRole('Staff'));
    }

    public function test_super_admin_without_selected_company_uses_global_branding_only(): void
    {
        $company = Company::query()->create(['name' => 'Acme', 'status' => 'active']);
        $superAdmin = $this->createSuperAdminWithSettingsAccess();

        Setting::withoutGlobalScopes()->create([
            'company_id' => null,
            'key' => 'project_title',
            'value' => 'Global Workspace',
        ]);

        Setting::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'key' => 'project_title',
            'value' => 'Tenant Workspace',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('Global Workspace');
        $response->assertDontSee('Tenant Workspace');
    }

    public function test_super_admin_can_update_global_settings_without_selecting_company(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();

        $response = $this->actingAs($superAdmin)->put(route('admin.settings.update'), [
            'project_title' => 'Root Control Center',
            'theme_preset' => 'cleopatra',
            'theme_mode' => 'dark',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertDatabaseHas('settings', [
            'company_id' => null,
            'key' => 'project_title',
            'value' => 'Root Control Center',
        ]);
    }

    public function test_super_admin_can_update_tenancy_base_domain_from_settings(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();

        $response = $this->actingAs($superAdmin)->put(route('admin.settings.update'), [
            'project_title' => 'Root Control Center',
            'theme_preset' => 'cleopatra',
            'theme_mode' => 'dark',
            'tenancy_base_domain' => 'platform.test',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertDatabaseHas('settings', [
            'company_id' => null,
            'key' => 'tenancy_base_domain',
            'value' => 'platform.test',
        ]);
    }

    public function test_super_admin_can_create_company_from_plain_subdomain(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();

        Setting::withoutGlobalScopes()->create([
            'company_id' => null,
            'key' => 'tenancy_base_domain',
            'value' => 'platform.test',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.companies.store'), [
            'name' => 'Somlogic',
            'domain' => 'somlogic',
            'status' => Company::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('admin.companies.index'));
        $this->assertDatabaseHas('companies', [
            'name' => 'Somlogic',
            'domain' => 'somlogic.platform.test',
            'status' => Company::STATUS_ACTIVE,
        ]);
    }

    public function test_super_admin_can_approve_pending_company_with_single_action(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();
        $company = Company::query()->create([
            'name' => 'Pending Co',
            'status' => Company::STATUS_PENDING,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.companies.approve', $company));

        $response->assertRedirect(route('admin.companies.index'));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => Company::STATUS_ACTIVE,
        ]);
    }

    public function test_super_admin_can_suspend_active_company(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();
        $company = Company::query()->create([
            'name' => 'Active Co',
            'status' => Company::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.companies.suspend', $company));

        $response->assertRedirect(route('admin.companies.index'));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => Company::STATUS_INACTIVE,
        ]);
    }

    public function test_super_admin_can_activate_inactive_company(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();
        $company = Company::query()->create([
            'name' => 'Inactive Co',
            'status' => Company::STATUS_INACTIVE,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.companies.activate', $company));

        $response->assertRedirect(route('admin.companies.index'));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => Company::STATUS_ACTIVE,
        ]);
    }

    public function test_super_admin_can_mark_company_back_to_pending(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();
        $company = Company::query()->create([
            'name' => 'Review Co',
            'status' => Company::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.companies.mark-pending', $company));

        $response->assertRedirect(route('admin.companies.index'));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => Company::STATUS_PENDING,
        ]);
    }

    public function test_super_admin_can_delete_company_and_tenant_records(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();
        $company = Company::query()->create([
            'name' => 'Delete Co',
            'status' => Company::STATUS_INACTIVE,
        ]);

        $user = User::factory()->create(['company_id' => $company->id]);
        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Tenant Role',
            'guard_name' => 'web',
            'is_locked' => false,
        ]);
        $permission = Permission::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'tenant-custom-permission',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('admin.companies.destroy', $company));

        $response->assertRedirect(route('admin.companies.index'));
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_super_admin_can_apply_bulk_approve_action(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();
        $firstPending = Company::query()->create(['name' => 'Pending 1', 'status' => Company::STATUS_PENDING]);
        $secondPending = Company::query()->create(['name' => 'Pending 2', 'status' => Company::STATUS_PENDING]);
        $inactive = Company::query()->create(['name' => 'Inactive', 'status' => Company::STATUS_INACTIVE]);

        $response = $this->actingAs($superAdmin)->post(route('admin.companies.bulk-lifecycle'), [
            'action' => 'approve',
            'companies' => [$firstPending->id, $secondPending->id, $inactive->id],
        ]);

        $response->assertRedirect(route('admin.companies.index'));
        $this->assertDatabaseHas('companies', ['id' => $firstPending->id, 'status' => Company::STATUS_ACTIVE]);
        $this->assertDatabaseHas('companies', ['id' => $secondPending->id, 'status' => Company::STATUS_ACTIVE]);
        $this->assertDatabaseHas('companies', ['id' => $inactive->id, 'status' => Company::STATUS_INACTIVE]);
    }

    public function test_company_status_change_writes_audit_activity_log(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();
        $company = Company::query()->create(['name' => 'Audit Co', 'status' => Company::STATUS_PENDING]);

        $this->actingAs($superAdmin)->post(route('admin.companies.approve', $company));

        $this->assertTrue(Activity::query()
            ->where('subject_type', Company::class)
            ->where('subject_id', $company->id)
            ->where('event', 'company-status-changed')
            ->where('description', 'Company status changed from pending to active')
            ->exists());
    }

    public function test_super_admin_can_switch_company_context_from_companies_actions(): void
    {
        $superAdmin = $this->createSuperAdminWithSettingsAccess();
        $company = Company::query()->create(['name' => 'Switch Co', 'status' => Company::STATUS_ACTIVE]);

        $response = $this->actingAs($superAdmin)->post(route('admin.companies.switch'), [
            'company_id' => $company->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('selected_company_id', $company->id);
    }

    protected function createSuperAdminWithSettingsAccess(): User
    {
        $user = User::factory()->create(['company_id' => null]);

        $permissions = collect(['read-setting', 'update-setting', 'edit-setting'])
            ->map(fn (string $name) => Permission::withoutGlobalScopes()->firstOrCreate([
                'company_id' => null,
                'name' => $name,
                'guard_name' => 'web',
            ]));

        $role = Role::withoutGlobalScopes()->firstOrCreate([
            'company_id' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $role->forceFill(['is_locked' => true])->save();
        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
    }
}
