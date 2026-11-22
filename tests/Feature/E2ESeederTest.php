<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Database\Seeders\E2ESeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class E2ESeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_deterministic_data_and_pages_render(): void
    {
        $this->seed(E2ESeeder::class);

        $this->assertSame(['E2E_Gold', 'E2E_Silver', 'E2E_Bronze'], User::where('progress_point', '>', 0)->orderByDesc('progress_point')->pluck('name')->all());
        $this->assertSame(1, News::count());

        $this->get('/')->assertOk()->assertSee('E2E_Gold');
        $this->get('/profile/E2E_Gold')->assertOk();
        $this->get('/news')->assertOk()->assertSee('E2E news title');
        $this->get('/shop')->assertOk();
        $this->assertTrue(User::where('name', 'E2E_Admin')->first()->hasRole('admin'));
    }

    public function test_the_e2e_login_route_does_not_exist_without_its_flag(): void
    {
        $this->seed(E2ESeeder::class);

        $this->get('/_e2e/login/E2E_Admin')->assertNotFound();
        $this->assertGuest();
    }
}
