<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_blocks_cross_company_model_access(): void
    {
        $companyA = Company::query()->create(['name' => 'Alpha', 'status' => 'active']);
        $companyB = Company::query()->create(['name' => 'Beta', 'status' => 'active']);

        $tenantUser = $this->createTenantAdmin($companyA, [
            'show-user',
            'update-user',
            'delete-user',
            'show-role',
            'update-role',
            'delete-role',
            'show-permission',
            'update-permission',
            'delete-permission',
            'read-setting',
            'update-setting',
            'delete-setting',
        ]);

        $foreignUser = User::factory()->create(['company_id' => $companyB->id]);
        $foreignRole = Role::withoutGlobalScopes()->create([
            'company_id' => $companyB->id,
            'name' => 'Foreign Role',
            'guard_name' => 'web',
        ]);
        $foreignPermission = Permission::withoutGlobalScopes()->create([
            'company_id' => $companyB->id,
            'name' => 'foreign-permission',
            'guard_name' => 'web',
        ]);
        $foreignSetting = Setting::withoutGlobalScopes()->create([
            'company_id' => $companyB->id,
            'key' => 'project_title',
            'value' => 'Beta Workspace',
        ]);

        $gate = Gate::forUser($tenantUser);

        $this->assertFalse($gate->allows('view', $foreignUser));
        $this->assertFalse($gate->allows('update', $foreignUser));
        $this->assertFalse($gate->allows('delete', $foreignUser));

        $this->assertFalse($gate->allows('view', $foreignRole));
        $this->assertFalse($gate->allows('update', $foreignRole));
        $this->assertFalse($gate->allows('delete', $foreignRole));

        $this->assertFalse($gate->allows('view', $foreignPermission));
        $this->assertFalse($gate->allows('update', $foreignPermission));
        $this->assertFalse($gate->allows('delete', $foreignPermission));

        $this->assertFalse($gate->allows('view', $foreignSetting));
        $this->assertFalse($gate->allows('update', $foreignSetting));
        $this->assertFalse($gate->allows('delete', $foreignSetting));
    }

    public function test_users_data_endpoint_is_company_isolated(): void
    {
        $companyA = Company::query()->create(['name' => 'Alpha', 'status' => 'active']);
        $companyB = Company::query()->create(['name' => 'Beta', 'status' => 'active']);

        $tenantUser = $this->createTenantAdmin($companyA, ['read-user']);

        User::factory()->create(['company_id' => $companyA->id, 'email' => 'alpha-member@example.com']);
        User::factory()->create(['company_id' => $companyB->id, 'email' => 'beta-member@example.com']);

        $response = $this->actingAs($tenantUser)->getJson(route('admin.users.data'));

        $response->assertOk();
        $response->assertSee('alpha-member@example.com');
        $response->assertDontSee('beta-member@example.com');
    }

    public function test_roles_data_endpoint_is_company_isolated(): void
    {
        $companyA = Company::query()->create(['name' => 'Alpha', 'status' => 'active']);
        $companyB = Company::query()->create(['name' => 'Beta', 'status' => 'active']);

        $tenantUser = $this->createTenantAdmin($companyA, ['read-role']);

        Role::withoutGlobalScopes()->create([
            'company_id' => $companyA->id,
            'name' => 'Alpha Analyst',
            'guard_name' => 'web',
        ]);

        Role::withoutGlobalScopes()->create([
            'company_id' => $companyB->id,
            'name' => 'Beta Analyst',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($tenantUser)->getJson(route('admin.roles.data'));

        $response->assertOk();
        $response->assertSee('Alpha Analyst');
        $response->assertDontSee('Beta Analyst');
    }

    public function test_permissions_data_endpoint_is_company_isolated(): void
    {
        $companyA = Company::query()->create(['name' => 'Alpha', 'status' => 'active']);
        $companyB = Company::query()->create(['name' => 'Beta', 'status' => 'active']);

        $tenantUser = $this->createTenantAdmin($companyA, ['read-permission']);

        Permission::withoutGlobalScopes()->create([
            'company_id' => $companyA->id,
            'name' => 'alpha-manage-catalog',
            'guard_name' => 'web',
        ]);

        Permission::withoutGlobalScopes()->create([
            'company_id' => $companyB->id,
            'name' => 'beta-manage-catalog',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($tenantUser)->getJson(route('admin.permissions.data'));

        $response->assertOk();
        $response->assertSee('alpha-manage-catalog');
        $response->assertDontSee('beta-manage-catalog');
    }

    public function test_settings_page_uses_selected_company_scope_for_super_admin(): void
    {
        $companyA = Company::query()->create(['name' => 'Alpha', 'status' => 'active']);
        $companyB = Company::query()->create(['name' => 'Beta', 'status' => 'active']);

        $superAdmin = $this->createSuperAdmin();

        Setting::withoutGlobalScopes()->create([
            'company_id' => $companyA->id,
            'key' => 'project_title',
            'value' => 'Alpha Workspace',
        ]);

        Setting::withoutGlobalScopes()->create([
            'company_id' => $companyB->id,
            'key' => 'project_title',
            'value' => 'Beta Workspace',
        ]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['selected_company_id' => $companyA->id])
            ->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('Alpha Workspace');
        $response->assertDontSee('Beta Workspace');
    }

    public function test_reports_page_is_company_isolated_for_super_admin_context(): void
    {
        $companyA = Company::query()->create(['name' => 'Alpha', 'status' => 'active']);
        $companyB = Company::query()->create(['name' => 'Beta', 'status' => 'active']);

        $superAdmin = $this->createSuperAdmin();

        Activity::query()->create([
            'company_id' => $companyA->id,
            'description' => 'alpha-activity-entry',
            'event' => 'updated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Activity::query()->create([
            'company_id' => $companyB->id,
            'description' => 'beta-activity-entry',
            'event' => 'updated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['selected_company_id' => $companyA->id])
            ->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('alpha-activity-entry');
        $response->assertDontSee('beta-activity-entry');
    }

    public function test_domain_based_resolution_sets_company_context_for_super_admin_when_enabled(): void
    {
        config()->set('tenancy.resolve_by_domain', true);

        $company = Company::query()->create([
            'name' => 'Domain Company',
            'domain' => 'tenant-alpha.test',
            'status' => 'active',
        ]);

        $superAdmin = $this->createSuperAdmin();

        Route::middleware(['web', 'auth', 'set-company-context'])
            ->get('/_test/company-context', function () {
                $context = app(CurrentCompanyContext::class);

                return response()->json([
                    'company_id' => $context->id(),
                    'bypass' => $context->bypassesTenancy(),
                ]);
            });

        $response = $this->actingAs($superAdmin)
            ->get('http://tenant-alpha.test/_test/company-context');

        $response->assertOk();
        $response->assertJson([
            'company_id' => $company->id,
            'bypass' => false,
        ]);
    }

    protected function createTenantAdmin(Company $company, array $permissionNames): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Tenant Admin '.$company->id.'-'.str()->random(5),
            'guard_name' => 'web',
        ]);

        $permissions = collect($permissionNames)->map(function (string $permissionName) {
            return Permission::withoutGlobalScopes()->firstOrCreate([
                'company_id' => null,
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        });

        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
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
}
