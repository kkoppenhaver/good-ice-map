<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocationStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/locations', [
                'name' => 'Test Spot',
                'latitude' => 40.7,
                'longitude' => -74.0,
                'address' => '123 Ice St',
            ])
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Location::count());
    }

    public function test_stores_single_primary_image_as_pending(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/locations', [
                'name' => 'Test Spot',
                'latitude' => 40.7,
                'longitude' => -74.0,
                'address' => '123 Ice St',
                'image' => UploadedFile::fake()->image('ice.jpg'),
            ])
            ->assertRedirect('/dashboard');

        $location = Location::firstOrFail();
        $this->assertSame('pending', $location->status);
        $this->assertSame(1, $location->images()->count());
        $this->assertTrue($location->images->first()->is_primary);
    }

    public function test_pending_locations_do_not_appear_on_public_api(): void
    {
        Location::create([
            'name' => 'Pending',
            'address' => '1 Ice Ave, Anywhere, USA',
            'latitude' => 40.0,
            'longitude' => -75.0,
            'submitted_by' => User::factory()->create()->id,
            'status' => 'pending',
        ]);

        $this->getJson('/api/locations')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_duplicate_address_redirects_to_existing_location(): void
    {
        Storage::fake('r2');
        $existing = Location::create([
            'name' => 'Hilton',
            'address' => '152 W 26th St, New York, NY 10001, USA',
            'latitude' => 40.7456,
            'longitude' => -73.9939,
            'submitted_by' => User::factory()->create()->id,
            'status' => 'approved',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/locations', [
                'name' => 'Hilton NY',
                'latitude' => 40.7456,
                'longitude' => -73.9939,
                'address' => '152 W 26th St, New York, NY 10001, USA',
                'image' => UploadedFile::fake()->image('ice.jpg'),
            ])
            ->assertRedirect("/locations/{$existing->id}")
            ->assertSessionHas('success');

        $this->assertSame(1, Location::count());
    }

    public function test_duplicate_check_is_case_and_whitespace_insensitive(): void
    {
        Storage::fake('r2');
        $existing = Location::create([
            'name' => 'Spot',
            'address' => '152 W 26th St, New York, NY 10001, USA',
            'latitude' => 40.7,
            'longitude' => -74.0,
            'submitted_by' => User::factory()->create()->id,
            'status' => 'approved',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/locations', [
                'name' => 'Spot',
                'latitude' => 40.7,
                'longitude' => -74.0,
                'address' => '  152 W 26TH ST,  New York, NY 10001, USA  ',
                'image' => UploadedFile::fake()->image('ice.jpg'),
            ])
            ->assertRedirect("/locations/{$existing->id}");

        $this->assertSame(1, Location::count());
    }

    public function test_different_address_creates_new_location(): void
    {
        Storage::fake('r2');
        Location::create([
            'name' => 'Spot A',
            'address' => '152 W 26th St, New York, NY 10001, USA',
            'latitude' => 40.7,
            'longitude' => -74.0,
            'submitted_by' => User::factory()->create()->id,
            'status' => 'approved',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/locations', [
                'name' => 'Spot B',
                'latitude' => 40.8,
                'longitude' => -74.1,
                'address' => '850 N Rand Rd, Lake Zurich, IL 60047, USA',
                'image' => UploadedFile::fake()->image('ice.jpg'),
            ])
            ->assertRedirect();

        $this->assertSame(2, Location::count());
    }

    public function test_pending_locations_do_not_block_new_submissions(): void
    {
        // Only approved locations should count as duplicates; pending submissions
        // shouldn't keep someone else from getting their address through the queue.
        Storage::fake('r2');
        Location::create([
            'name' => 'Pending Spot',
            'address' => '500 Some Ave, Brooklyn, NY 11201, USA',
            'latitude' => 40.7,
            'longitude' => -73.9,
            'submitted_by' => User::factory()->create()->id,
            'status' => 'pending',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/locations', [
                'name' => 'Same Spot',
                'latitude' => 40.7,
                'longitude' => -73.9,
                'address' => '500 Some Ave, Brooklyn, NY 11201, USA',
                'image' => UploadedFile::fake()->image('ice.jpg'),
            ])
            ->assertRedirect();

        $this->assertSame(2, Location::count());
    }
}
