<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makePending(): Location
    {
        return Location::create([
            'name' => 'Spot',
            'address' => '1 Ice St',
            'latitude' => 40.0,
            'longitude' => -75.0,
            'submitted_by' => User::factory()->create()->id,
            'status' => 'pending',
        ]);
    }

    public function test_guest_cannot_view_admin_queue(): void
    {
        $this->get('/admin/locations')->assertRedirect('/login');
    }

    public function test_non_admin_gets_forbidden_on_queue(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/locations')
            ->assertForbidden();
    }

    public function test_admin_sees_only_pending_locations(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pending = $this->makePending();

        Location::create([
            'name' => 'Approved Spot',
            'address' => '2 Ice St',
            'latitude' => 41.0,
            'longitude' => -76.0,
            'submitted_by' => User::factory()->create()->id,
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->get('/admin/locations')
            ->assertOk()
            ->assertSee($pending->name)
            ->assertDontSee('Approved Spot');
    }

    public function test_admin_can_approve_a_pending_location(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $location = $this->makePending();

        $this->actingAs($admin)
            ->post("/admin/locations/{$location->id}/approve")
            ->assertRedirect('/admin/locations');

        $this->assertSame('approved', $location->fresh()->status);
    }

    public function test_admin_can_reject_a_pending_location(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $location = $this->makePending();

        $this->actingAs($admin)
            ->post("/admin/locations/{$location->id}/reject")
            ->assertRedirect('/admin/locations');

        $this->assertSame('rejected', $location->fresh()->status);
    }

    public function test_non_admin_cannot_approve(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $location = $this->makePending();

        $this->actingAs($user)
            ->post("/admin/locations/{$location->id}/approve")
            ->assertForbidden();

        $this->assertSame('pending', $location->fresh()->status);
    }

    public function test_non_admin_cannot_reject(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $location = $this->makePending();

        $this->actingAs($user)
            ->post("/admin/locations/{$location->id}/reject")
            ->assertForbidden();

        $this->assertSame('pending', $location->fresh()->status);
    }

    public function test_dashboard_hides_rejected_submissions(): void
    {
        $owner = User::factory()->create();
        Location::create([
            'name' => 'Rejected Spot',
            'address' => '99 Bad St',
            'latitude' => 40.0,
            'longitude' => -75.0,
            'submitted_by' => $owner->id,
            'status' => 'rejected',
        ]);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Rejected Spot');
    }
}
