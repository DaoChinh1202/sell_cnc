<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_logo_asset_has_the_expected_dimensions(): void
    {
        $path = public_path('assets/images/khomau3d-logo.webp');
        $this->assertFileExists($path);
        $image = getimagesize($path);
        $this->assertSame(600, $image[0]);
        $this->assertSame(400, $image[1]);
        $this->assertSame('image/webp', $image['mime']);
    }

    public function test_public_and_auth_pages_use_the_new_logo(): void
    {
        foreach ([
            route('home'),
            route('storefront.products.index'),
            route('signin'),
            route('password.request'),
            route('password.reset', ['token' => 'example-token', 'email' => 'admin@example.com']),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('src="'.asset('assets/images/khomau3d-logo.webp').'"', false)
                ->assertDontSee('khomau3d_logo_no_tagline.svg', false);
        }
    }

    public function test_not_found_page_uses_the_new_logo(): void
    {
        $this->get('/missing-branding-page')->assertNotFound()
            ->assertSee('src="'.asset('assets/images/khomau3d-logo.webp').'"', false)
            ->assertDontSee('khomau3d_logo_no_tagline.svg', false);
    }
}
