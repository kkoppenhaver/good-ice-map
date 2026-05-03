<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationShowTest extends TestCase
{
    use RefreshDatabase;

    private function makeLocation(string $status, ?User $owner = null): Location
    {
        return Location::create([
            'name' => 'Spot',
            'address' => '1 Ice St',
            'latitude' => 40.0,
            'longitude' => -75.0,
            'submitted_by' => ($owner ?? User::factory()->create())->id,
            'status' => $status,
        ]);
    }

    public function test_approved_location_visible_to_guest(): void
    {
        $location = $this->makeLocation('approved');

        $this->get("/locations/{$location->id}")->assertOk();
    }

    public function test_pending_location_404s_for_guest(): void
    {
        $location = $this->makeLocation('pending');

        $this->get("/locations/{$location->id}")->assertForbidden();
    }

    public function test_pending_location_404s_for_non_submitter(): void
    {
        $location = $this->makeLocation('pending');
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get("/locations/{$location->id}")
            ->assertForbidden();
    }

    public function test_pending_location_visible_to_submitter(): void
    {
        $owner = User::factory()->create();
        $location = $this->makeLocation('pending', $owner);

        $this->actingAs($owner)
            ->get("/locations/{$location->id}")
            ->assertOk()
            ->assertSee('Pending Review');
    }

    public function test_pending_location_visible_to_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $location = $this->makeLocation('pending');

        $this->actingAs($admin)
            ->get("/locations/{$location->id}")
            ->assertOk();
    }

    public function test_rejected_location_404s_for_submitter(): void
    {
        $owner = User::factory()->create();
        $location = $this->makeLocation('rejected', $owner);

        $this->actingAs($owner)
            ->get("/locations/{$location->id}")
            ->assertForbidden();
    }

    public function test_rejected_location_visible_to_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $location = $this->makeLocation('rejected');

        $this->actingAs($admin)
            ->get("/locations/{$location->id}")
            ->assertOk();
    }
}
