<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use app\Models\User;
use app\Models\Role;
use app\Models\StaffType;
use app\Enums\Roles as RoleEnum;
use app\Enums\StaffTypes as StaffTypeEnum;

class UserRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed all reference tables using DatabaseSeeder
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Create a regular user
        $this->regularUser = User::factory()->create([
            'role_id' => Role::where('name', RoleEnum::CUSTOMER_RELATION->value)->first()->id,
            'staff_type_id' => StaffType::where('name', StaffTypeEnum::PHYSICAL_STAFF->value)->first()->id,
            'activated' => 1,
        ]);

        // Create an HR user
        $this->hrUser = User::factory()->create([
            'role_id' => Role::where('name', RoleEnum::HUMAN_RESOURCE->value)->first()->id,
            'staff_type_id' => StaffType::where('name', StaffTypeEnum::PHYSICAL_STAFF->value)->first()->id,
            'activated' => 1,
        ]);

        // Create a Super Admin user
        $this->superAdmin = User::factory()->create([
            'role_id' => Role::where('name', RoleEnum::SUPER_ADMIN->value)->first()->id,
            'staff_type_id' => StaffType::where('name', StaffTypeEnum::PHYSICAL_STAFF->value)->first()->id,
            'activated' => 1,
        ]);
    }

    /**
     * Test that all routes return 401 if unauthenticated.
     */
    public function test_unauthenticated_requests_are_unauthorized()
    {
        $endpoints = [
            ['GET', '/api/v2/user/team'],
            ['GET', '/api/v2/user/dashboard'],
            ['GET', '/api/v2/user/transactions'],
            ['GET', '/api/v2/user/profile'],
            ['GET', '/api/v2/user/staffs'],
            ['GET', '/api/v2/user/project_types'],
            ['GET', '/api/v2/user/projects'],
            ['GET', '/api/v2/user/packages'],
            ['GET', '/api/v2/user/assets'],
            ['GET', '/api/v2/user/offers'],
            ['GET', '/api/v2/user/site_tour/schedules'],
            ['GET', '/api/v2/user/posts'],
            ['GET', '/api/v2/user/promos'],
            ['GET', '/api/v2/user/staff_bank_accounts'],
            ['GET', '/api/v2/user/referrals/earnings'],
            ['GET', '/api/v2/user/bonds'],
            ['GET', '/api/v2/user/notifications/unread'],
            ['GET', '/api/v2/user/discounts/full_payment'],
            ['GET', '/api/v2/user/clients'],
            ['GET', '/api/v2/user/assessments'],
            ['GET', '/api/v2/user/analytics/sales_overview'],
            ['GET', '/api/v2/user/virtual_teams/applications'],
            ['GET', '/api/v2/user/roles'],
            ['GET', '/api/v2/user/staff_types'],
            ['GET', '/api/v2/user/bank_accounts'],
            ['GET', '/api/v2/user/resell_orders'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = $this->json($method, $uri);
            $response->assertStatus(401);
        }
    }

    /**
     * Test GET routes that are accessible to any authenticated user.
     */
    public function test_authenticated_user_can_access_general_routes()
    {
        $this->actingAs($this->regularUser);

        // 1. Roles & Utility endpoints
        $this->getJson('/api/v2/user/roles')->assertStatus(200);
        $this->getJson('/api/v2/user/staff_types')->assertStatus(200);
        $this->getJson('/api/v2/user/bank_accounts')->assertStatus(200);
        $this->getJson('/api/v2/user/resell_orders')->assertStatus(200);

        // 2. Dashboard & Charts
        $this->getJson('/api/v2/user/dashboard')->assertStatus(200);
        $this->getJson('/api/v2/user/dashboard/purchase_chart')->assertStatus(200);

        // 3. Transactions
        $this->getJson('/api/v2/user/transactions')->assertStatus(200);

        // 4. Team
        $this->getJson('/api/v2/user/team')->assertStatus(200);

        // 5. Project & Project Types
        $this->getJson('/api/v2/user/project_types')->assertStatus(200);
        $this->getJson('/api/v2/user/projects')->assertStatus(200);
        $this->getJson('/api/v2/user/projects/types')->assertStatus(200);

        // 6. Packages
        $this->getJson('/api/v2/user/packages')->assertStatus(200);

        // 7. Assets
        $this->getJson('/api/v2/user/assets')->assertStatus(200);

        // 8. Offers
        $this->getJson('/api/v2/user/offers')->assertStatus(200);
        $this->getJson('/api/v2/user/offers/ready')->assertStatus(200);

        // 9. Site Tour
        $this->getJson('/api/v2/user/site_tour/schedules')->assertStatus(200);
        $this->getJson('/api/v2/user/site_tour/schedules/booked')->assertStatus(200);

        // 10. Posts
        $this->getJson('/api/v2/user/posts')->assertStatus(200);

        // 11. Promos
        $this->getJson('/api/v2/user/promos')->assertStatus(200);
        $this->getJson('/api/v2/user/promos/promo_codes')->assertStatus(200);

        // 12. Staff Bank Accounts
        $this->getJson('/api/v2/user/staff_bank_accounts')->assertStatus(200);

        // 13. Referrals
        $this->getJson('/api/v2/user/referrals/earnings')->assertStatus(200);
        $this->getJson('/api/v2/user/referrals/redemptions')->assertStatus(200);

        // 14. Bonds
        $this->getJson('/api/v2/user/bonds')->assertStatus(200);
        $this->getJson('/api/v2/user/bonds/summary')->assertStatus(200);
        $this->getJson('/api/v2/user/bonds/requests')->assertStatus(200);

        // 15. Notifications
        $this->getJson('/api/v2/user/notifications/unread')->assertStatus(200);

        // 16. Discounts
        $this->getJson('/api/v2/user/discounts/full_payment')->assertStatus(200);
        $this->getJson('/api/v2/user/discounts/installments')->assertStatus(200);

        // 17. Clients
        $this->getJson('/api/v2/user/clients')->assertStatus(200);

        // 18. Assessments
        $this->getJson('/api/v2/user/assessments')->assertStatus(200);

        // 19. Analytics
        $this->getJson('/api/v2/user/analytics/sales_overview')->assertStatus(200);
        $this->getJson('/api/v2/user/analytics/project_types')->assertStatus(200);

        // 20. Virtual Teams
        $this->getJson('/api/v2/user/virtual_teams/applications')->assertStatus(200);
    }

    /**
     * Test routes that require HR Auth.
     */
    public function test_hr_routes_enforce_hr_role()
    {
        // A regular user should be forbidden / unauthorized (401)
        $this->actingAs($this->regularUser);
        $this->getJson('/api/v2/user/staffs/activities')->assertStatus(401);
        $this->getJson('/api/v2/user/job_adverts')->assertStatus(401);
        $this->getJson('/api/v2/user/admin_referrals')->assertStatus(401);

        // HR user should be able to access them
        $this->actingAs($this->hrUser);
        $this->getJson('/api/v2/user/staffs/activities')->assertStatus(200);
        $this->getJson('/api/v2/user/job_adverts')->assertStatus(200);
        $this->getJson('/api/v2/user/admin_referrals')->assertStatus(200);
    }

    /**
     * Test routes that require Super Admin Auth.
     */
    public function test_super_admin_routes_enforce_super_admin_role()
    {
        // Regular user should be unauthorized
        $this->actingAs($this->regularUser);
        $this->getJson('/api/v2/user/staffs/reset/1')->assertStatus(401);

        // HR user should be unauthorized
        $this->actingAs($this->hrUser);
        $this->getJson('/api/v2/user/staffs/reset/1')->assertStatus(401);

        // Super Admin should be able to access it (getting 200 or custom response logic, not 401)
        $this->actingAs($this->superAdmin);
        $response = $this->getJson('/api/v2/user/staffs/reset/9999');
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    /**
     * Test that user history is logged when staff_type_id changes.
     */
    public function test_user_history_logged_on_staff_type_change()
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', RoleEnum::CUSTOMER_RELATION->value)->first()->id,
            'staff_type_id' => StaffType::where('name', StaffTypeEnum::PHYSICAL_STAFF->value)->first()->id,
            'activated' => 1,
        ]);

        $newStaffType = StaffType::where('name', StaffTypeEnum::HYBRID_STAFF->value)->first();

        // Change staff_type_id
        $user->staff_type_id = $newStaffType->id;
        $user->save();

        $expectedAction = "Staff type changed from " . StaffTypeEnum::PHYSICAL_STAFF->value . " to " . StaffTypeEnum::HYBRID_STAFF->value;

        $this->assertDatabaseHas('user_histories', [
            'user_id' => $user->id,
            'action' => $expectedAction,
        ]);
    }

    public function test_staff_type_user_summary_view_works()
    {
        $physicalStaffType = StaffType::where('name', StaffTypeEnum::PHYSICAL_STAFF->value)->first();

        // Get initial counts from view
        $initial = \Illuminate\Support\Facades\DB::table('staff_type_user_summary')
            ->where('staff_type_id', $physicalStaffType->id)
            ->first();
        $initialTotal = $initial ? $initial->total_users : 0;
        $initialActive = $initial ? $initial->active_users : 0;

        // Create 2 active users with physical staff type
        User::factory()->count(2)->create([
            'role_id' => Role::where('name', RoleEnum::CUSTOMER_RELATION->value)->first()->id,
            'staff_type_id' => $physicalStaffType->id,
            'activated' => 1,
        ]);

        // Query the database view again
        $summary = \Illuminate\Support\Facades\DB::table('staff_type_user_summary')
            ->where('staff_type_id', $physicalStaffType->id)
            ->first();

        $this->assertNotNull($summary);
        $this->assertEquals(StaffTypeEnum::PHYSICAL_STAFF->value, $summary->staff_type_name);
        $this->assertEquals($initialTotal + 2, $summary->total_users);
        $this->assertEquals($initialActive + 2, $summary->active_users);
    }
}
