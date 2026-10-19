<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function makeLocation(array $overrides = []): Location
    {
        return Location::create(array_merge([
            'name' => 'Hilton New York Fashion District',
            'address' => '152 W 26th St, New York, NY 10001, USA',
            'latitude' => 40.7455256,
            'longitude' => -73.9937621,
            'submitted_by' => User::factory()->create()->id,
            'status' => 'approved',
        ], $overrides));
    }

    public function test_location_url_uses_id_and_slug(): void
    {
        $location = $this->makeLocation();

        $this->assertSame(
            url("/locations/{$location->id}-hilton-new-york-fashion-district"),
            $location->url,
        );
        $this->assertSame($location->url, route('locations.show', $location));
    }

    public function test_bare_id_url_permanently_redirects_to_canonical(): void
    {
        $location = $this->makeLocation();

        $this->get("/locations/{$location->id}")
            ->assertStatus(301)
            ->assertRedirect($location->url);
    }

    public function test_stale_slug_permanently_redirects_to_canonical(): void
    {
        $location = $this->makeLocation();

        $this->get("/locations/{$location->id}-old-name")
            ->assertStatus(301)
            ->assertRedirect($location->url);
    }

    public function test_unknown_id_404s(): void
    {
        $this->get('/locations/999999-nope')->assertNotFound();
    }

    public function test_approved_location_has_meta_canonical_and_structured_data(): void
    {
        $location = $this->makeLocation([
            'description' => 'Ice machine by the gym is great.',
            'average_rating' => 4.5,
            'total_ratings' => 2,
        ]);

        $response = $this->get($location->url)->assertOk();

        $response->assertSee('<link rel="canonical" href="'.$location->url.'">', false);
        $response->assertSee('<meta name="description" content="Good ice at Hilton New York Fashion District, 152 W 26th St, New York, NY 10001, USA. Rated 4.5/5 by 2 ice chewers. Ice machine by the gym is great."', false);
        $response->assertSee('property="og:title" content="Hilton New York Fashion District - Good Ice Map"', false);
        $response->assertSee('"@type":"LocalBusiness"', false);
        $response->assertSee('"ratingValue":4.5', false);
        $response->assertSee('"ratingCount":2', false);
        $response->assertSee('{"@context":"https://schema.org","@type":"LocalBusiness"', false);
        $response->assertSee('{"@context":"https://schema.org","@type":"BreadcrumbList"', false);
        $response->assertDontSee('__contextArgs', false);
        $response->assertDontSee('noindex', false);
    }

    public function test_approved_location_without_ratings_omits_aggregate_rating(): void
    {
        $location = $this->makeLocation();

        $this->get($location->url)
            ->assertOk()
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertDontSee('AggregateRating', false);
    }

    public function test_pending_location_is_noindex_for_submitter(): void
    {
        $owner = User::factory()->create();
        $location = $this->makeLocation(['status' => 'pending', 'submitted_by' => $owner->id]);

        $this->actingAs($owner)
            ->get($location->url)
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('rel="canonical"', false)
            ->assertDontSee('application/ld+json', false);
    }

    public function test_api_locations_include_canonical_url(): void
    {
        $location = $this->makeLocation();

        $this->getJson('/api/locations')
            ->assertOk()
            ->assertJsonFragment(['url' => $location->url]);
    }

    public function test_sitemap_lists_static_pages_and_approved_locations_only(): void
    {
        $approved = $this->makeLocation();
        $pending = $this->makeLocation(['name' => 'Pending Spot', 'status' => 'pending']);
        $rejected = $this->makeLocation(['name' => 'Rejected Spot', 'status' => 'rejected']);

        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $response->assertSee('<loc>'.route('home').'</loc>', false);
        $response->assertSee('<loc>'.route('map').'</loc>', false);
        $response->assertSee('<loc>'.$approved->url.'</loc>', false);
        $response->assertSee('<lastmod>', false);
        $response->assertDontSee($pending->url, false);
        $response->assertDontSee($rejected->url, false);
    }

    public function test_landing_page_has_website_structured_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('{"@context":"https://schema.org","@type":"WebSite"', false)
            ->assertDontSee('__contextArgs', false);
    }

    public function test_map_page_has_meta_description_and_canonical(): void
    {
        $this->get('/map')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('map').'">', false)
            ->assertSee('<meta name="description"', false);
    }

    public function test_robots_txt_points_to_sitemap_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.route('sitemap'), false)
            ->assertSee("Disallow: /admin\n", false)
            ->assertDontSee("Disallow: /\n", false);
    }

    public function test_robots_txt_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee("Disallow: /\n", false)
            ->assertDontSee('Sitemap:', false);
    }

    public function test_json_ld_cannot_break_out_of_script_tag(): void
    {
        $payload = '</script><script>alert(1)</script>';
        $location = $this->makeLocation([
            'name' => 'Evil '.$payload,
            'address' => 'Addr '.$payload,
            'description' => 'Desc '.$payload,
        ]);

        $response = $this->get($location->url)->assertOk();

        $this->assertStringNotContainsString($payload, $response->getContent());
        $this->assertStringNotContainsString('<script>alert', $response->getContent());

        // The encoded form must still decode back to the original text.
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $m);
        $this->assertCount(2, $m[1]);
        $this->assertSame('Evil '.$payload, json_decode($m[1][0], true)['name']);
    }

    public function test_redirect_preserves_query_string(): void
    {
        $location = $this->makeLocation();

        $this->get("/locations/{$location->id}?utm_source=reddit&utm_campaign=ice")
            ->assertStatus(301)
            ->assertRedirect($location->url.'?utm_source=reddit&utm_campaign=ice');
    }

    public function test_soft_deleted_location_404s_on_both_url_forms(): void
    {
        $location = $this->makeLocation();
        $url = $location->url;
        $location->delete();

        $this->get("/locations/{$location->id}")->assertNotFound();
        $this->get($url)->assertNotFound();
    }

    public function test_name_that_slugifies_to_nothing_falls_back(): void
    {
        $location = $this->makeLocation(['name' => '???']);

        $this->assertSame('location', $location->slug);
        $this->assertSame(url("/locations/{$location->id}-location"), $location->url);
        $this->get($location->url)->assertOk();
    }

    public function test_update_and_delete_work_against_slug_route_key(): void
    {
        $owner = User::factory()->create();
        $location = $this->makeLocation(['submitted_by' => $owner->id]);

        $this->actingAs($owner)
            ->patch($location->url, ['name' => 'Renamed Spot'])
            ->assertRedirect(route('locations.show', $location->fresh()));
        $this->assertSame('Renamed Spot', $location->fresh()->name);

        $this->actingAs($owner)
            ->delete($location->fresh()->url)
            ->assertRedirect();
        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }

    public function test_pending_location_sends_x_robots_tag_header(): void
    {
        $owner = User::factory()->create();
        $location = $this->makeLocation(['status' => 'pending', 'submitted_by' => $owner->id]);

        $this->actingAs($owner)
            ->get($location->url)
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->get($this->makeLocation(['name' => 'Fine Spot'])->url)
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_sitemap_cache_is_busted_when_a_location_changes(): void
    {
        $location = $this->makeLocation();
        $this->get('/sitemap.xml')->assertSee($location->url, false);

        $location->update(['status' => 'rejected']);
        $this->get('/sitemap.xml')->assertDontSee($location->url, false);

        $location->update(['status' => 'approved']);
        $this->get('/sitemap.xml')->assertSee($location->url, false);

        $location->delete();
        $this->get('/sitemap.xml')->assertDontSee($location->url, false);
    }
}
