<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_create_ticket_and_only_see_own_company_tickets(): void
    {
        $companyA = Company::query()->create(['name' => 'Alpha', 'status' => 'active']);
        $companyB = Company::query()->create(['name' => 'Beta', 'status' => 'active']);

        $tenantUser = $this->createTenantUserWithTicketPermissions($companyA);

        SupportTicket::query()->create([
            'company_id' => $companyB->id,
            'created_by_user_id' => User::factory()->create(['company_id' => $companyB->id])->id,
            'ticket_number' => 'TKT-BETA-001',
            'subject' => 'Beta outage',
            'category' => 'infrastructure',
            'priority' => SupportTicket::PRIORITY_HIGH,
            'status' => SupportTicket::STATUS_OPEN,
            'description' => 'Cross-company ticket should stay hidden.',
        ]);

        $createResponse = $this->actingAs($tenantUser)->post(route('admin.tickets.store'), [
            'subject' => 'Cannot export report',
            'category' => 'exports',
            'priority' => SupportTicket::PRIORITY_MEDIUM,
            'description' => 'Export fails with timeout after 20 seconds.',
        ]);

        $createResponse->assertRedirect();

        $indexResponse = $this->actingAs($tenantUser)->get(route('admin.tickets.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Cannot export report');
        $indexResponse->assertDontSee('Beta outage');
    }

    public function test_super_admin_can_resolve_ticket_for_selected_company_scope(): void
    {
        $company = Company::query()->create(['name' => 'Gamma', 'status' => 'active']);

        $creator = User::factory()->create(['company_id' => $company->id]);
        $ticket = SupportTicket::query()->create([
            'company_id' => $company->id,
            'created_by_user_id' => $creator->id,
            'ticket_number' => 'TKT-GAMMA-001',
            'subject' => 'Payment webhook issue',
            'category' => 'billing',
            'priority' => SupportTicket::PRIORITY_HIGH,
            'status' => SupportTicket::STATUS_IN_PROGRESS,
            'description' => 'Webhook verification fails in production.',
        ]);

        $superAdmin = $this->createSuperAdminWithTicketPermissions();

        $response = $this->actingAs($superAdmin)
            ->withSession(['selected_company_id' => $company->id])
            ->put(route('admin.tickets.update', $ticket), [
                'status' => SupportTicket::STATUS_RESOLVED,
                'priority' => SupportTicket::PRIORITY_HIGH,
                'assigned_to_user_id' => $superAdmin->id,
            ]);

        $response->assertRedirect();

        $ticket->refresh();

        $this->assertSame(SupportTicket::STATUS_RESOLVED, $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertSame($superAdmin->id, $ticket->assigned_to_user_id);
    }

    public function test_super_admin_can_filter_ticket_queue_by_company_assignee_and_category(): void
    {
        $alpha = Company::query()->create(['name' => 'Alpha', 'status' => 'active']);
        $beta = Company::query()->create(['name' => 'Beta', 'status' => 'active']);

        $assignee = User::factory()->create(['company_id' => $alpha->id, 'name' => 'Queue Owner']);
        $otherAssignee = User::factory()->create(['company_id' => $beta->id, 'name' => 'Other Owner']);
        $creator = User::factory()->create(['company_id' => $alpha->id]);

        SupportTicket::query()->create([
            'company_id' => $alpha->id,
            'created_by_user_id' => $creator->id,
            'assigned_to_user_id' => $assignee->id,
            'ticket_number' => 'TKT-ALPHA-001',
            'subject' => 'Webhook backlog',
            'category' => 'billing',
            'priority' => SupportTicket::PRIORITY_URGENT,
            'status' => SupportTicket::STATUS_OPEN,
            'description' => 'Billing queue needs triage.',
        ]);

        SupportTicket::query()->create([
            'company_id' => $beta->id,
            'created_by_user_id' => User::factory()->create(['company_id' => $beta->id])->id,
            'assigned_to_user_id' => $otherAssignee->id,
            'ticket_number' => 'TKT-BETA-001',
            'subject' => 'Ops incident',
            'category' => 'operations',
            'priority' => SupportTicket::PRIORITY_HIGH,
            'status' => SupportTicket::STATUS_IN_PROGRESS,
            'description' => 'Should be excluded by filters.',
        ]);

        $superAdmin = $this->createSuperAdminWithTicketPermissions();

        $response = $this->actingAs($superAdmin)->get(route('admin.tickets.index', [
            'company_id' => $alpha->id,
            'assigned_to_user_id' => $assignee->id,
            'category' => 'billing',
            'status' => SupportTicket::STATUS_OPEN,
        ]));

        $response->assertOk();
        $response->assertSee('Webhook backlog');
        $response->assertSee('Alpha');
        $response->assertSee('Queue Owner');
        $response->assertDontSee('Ops incident');
    }

    protected function createTenantUserWithTicketPermissions(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Support Tenant '.$company->id,
            'guard_name' => 'web',
        ]);

        $permissions = collect([
            'read-ticket',
            'show-ticket',
            'create-ticket',
            'update-ticket',
        ])->map(fn (string $name) => Permission::withoutGlobalScopes()->firstOrCreate([
            'company_id' => null,
            'name' => $name,
            'guard_name' => 'web',
        ]));

        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
    }

    protected function createSuperAdminWithTicketPermissions(): User
    {
        $user = User::factory()->create(['company_id' => null]);

        $role = Role::withoutGlobalScopes()->firstOrCreate([
            'company_id' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $permissions = collect([
            'read-ticket',
            'show-ticket',
            'create-ticket',
            'update-ticket',
        ])->map(fn (string $name) => Permission::withoutGlobalScopes()->firstOrCreate([
            'company_id' => null,
            'name' => $name,
            'guard_name' => 'web',
        ]));

        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
    }
}
