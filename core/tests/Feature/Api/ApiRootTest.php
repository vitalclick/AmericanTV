<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class ApiRootTest extends TestCase
{
    public function test_api_v1_root_returns_service_info_json(): void
    {
        $response = $this->getJson('/api/v1');

        $response->assertOk();
        $response->assertJsonStructure([
            'service',
            'version',
            'status',
            'time',
            'docs',
        ]);
        $response->assertJsonPath('service', 'americantv-api');
        $response->assertJsonPath('version', 'v1');
        $response->assertJsonPath('status', 'ok');
    }

    public function test_a_missing_api_v1_route_still_returns_json_not_html(): void
    {
        // The bootstrap/app.php exception renderer routes 404s under
        // api/* to JSON. This pins that behaviour — a regression that
        // restored Laravel's HTML 404 would break every mobile client.
        $response = $this->getJson('/api/v1/this-route-does-not-exist');

        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_openapi_spec_is_served_when_deployed(): void
    {
        // The spec lives in core/docs/api/. If it's present (which it
        // should be on every deploy), the endpoint streams it with
        // application/yaml. If it isn't, we fall back to a clean 404.
        $response = $this->get('/api/v1/openapi.yaml');

        if (file_exists(base_path('docs/api/openapi-v1.yaml'))) {
            $response->assertOk();
            $response->assertHeader('Content-Type', 'application/yaml');
        } else {
            $response->assertStatus(404);
        }
    }
}
