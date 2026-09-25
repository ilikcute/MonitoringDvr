<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Pastikan SPA entrypoint merender halaman dengan kontainer id="app" dan aset Vite.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<div id="app"', false);
        $response->assertSee('CDAMS - CCTV Asset & Access Management System', false);
    }

    public function test_spa_routing_fallback_serves_app_view(): void
    {
        $response = $this->get('/stores');
        $response->assertStatus(200);
        $response->assertSee('<div id="app"', false);
    }
}
