<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationEditTest extends TestCase
{
    use RefreshDatabase;

    private function makeLocation(User $owner): Location
    {
        return Location::create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'address' => '123 Ice St',
            'latitude' => 40.0,
            'longitude' => -75.0,
            'submitted_by' => $owner->id,
            'status' => 'approved',
        ]);
    }

    public function test_submitter_sees_edit_form(): void
    {
        $user = User::factory()->create();
        $location = $this->makeLocation($user);

        $this->actingAs($user)
            ->get("/locations/{$location->id}/edit")
            ->assertOk()
            ->assertSee('Edit Location')
            ->assertSee('Original Name', false);
    }

    public function test_non_submitter_cannot_see_edit_form(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $location = $this->makeLocation($owner);

        $this->actingAs($stranger)
            ->get("/locations/{$location->id}/edit")
            ->assertForbidden();
    }

    public function test_guest_cannot_see_edit_form(): void
    {
        $owner = User::factory()->create();
        $location = $this->makeLocation($owner);

        $this->get("/locations/{$location->id}/edit")
            ->assertRedirect('/login');
    }

    public function test_submitter_can_update_name_and_description(): void
    {
        $user = User::factory()->create();
        $location = $this->makeLocation($user);

        $this->actingAs($user)
            ->patch("/locations/{$location->id}", [
                'name' => 'Updated Name',
                'description' => 'Updated description',
            ])
            ->assertRedirect("/locations/{$location->id}");

        $location->refresh();
        $this->assertSame('Updated Name', $location->name);
        $this->assertSame('Updated description', $location->description);
    }

    public function test_update_does_not_change_address_or_coordinates(): void
    {
        $user = User::factory()->create();
        $location = $this->makeLocation($user);

        $this->actingAs($user)
            ->patch("/locations/{$location->id}", [
                'name' => 'New Name',
                'description' => 'New description',
                'address' => 'HACKED',
                'latitude' => 0.0,
                'longitude' => 0.0,
            ])
            ->assertRedirect();

        $location->refresh();
        $this->assertSame('123 Ice St', $location->address);
        $this->assertEquals(40.0, $location->latitude);
        $this->assertEquals(-75.0, $location->longitude);
    }

    public function test_non_submitter_cannot_update(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $location = $this->makeLocation($owner);

        $this->actingAs($stranger)
            ->patch("/locations/{$location->id}", [
                'name' => 'Hijacked',
            ])
            ->assertForbidden();

        $this->assertSame('Original Name', $location->fresh()->name);
    }
}
