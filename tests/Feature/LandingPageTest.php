<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('VulnHunt');
        $response->assertSee('brand/logo-nav.webp');
        $response->assertSee('Daftar Tim Sekarang');
        $response->assertSee('Alur Mengikuti Kompetisi');
        $response->assertSee('Pertanyaan yang Sering Diajukan');
    }

    public function test_landing_page_shows_authenticated_actions_when_logged_in(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Buka Dasbor');
    }
}
