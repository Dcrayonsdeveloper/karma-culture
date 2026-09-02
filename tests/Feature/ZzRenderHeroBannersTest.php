<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ZzRenderHeroBannersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql.database' => 'Karmaculture']);
        \Illuminate\Support\Facades\DB::purge('mysql');
    }

    public function test_dump_hero_banners_html(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $pages = [
            'hero-banners' => '/admin/homepage/hero-banners',
            'qualities' => '/admin/homepage/qualities',
            'testimonials' => '/admin/homepage/testimonials',
        ];

        foreach ($pages as $slug => $url) {
            $html = $this->actingAs($admin, 'admin')->get($url)->getContent();
            file_put_contents(base_path("storage/app/kk-{$slug}.html"), $html);
        }

        $this->assertTrue(true);
    }
}
