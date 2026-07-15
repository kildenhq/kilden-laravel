<?php

declare(strict_types=1);

namespace Kilden\Laravel\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Blade;
use Kilden\Laravel\FrontendSnippet;
use Kilden\Laravel\KildenRoutes;

class FrontendSnippetTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        // The base TestCase disables the SDK ("no network"); the snippet only
        // renders markup, so turn it back on. No event is ever tracked here.
        $app['config']->set('kilden.enabled', true);
        $app['config']->set('kilden.frontend.write_key', 'wk_test_public');
    }

    public function test_renders_loader_and_init_with_the_public_key(): void
    {
        $html = FrontendSnippet::render();

        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('https://cdn.kilden.io/kilden.iife.js', $html);
        $this->assertStringContainsString('kilden.init("wk_test_public"', $html);
        $this->assertStringNotContainsString('sk_test_secret', $html);
    }

    public function test_blade_directive_compiles_to_the_snippet(): void
    {
        $html = Blade::render('@kildenScript');

        $this->assertStringContainsString('kilden.init("wk_test_public"', $html);
    }

    public function test_renders_nothing_without_a_public_key(): void
    {
        config(['kilden.frontend.write_key' => null]);

        $this->assertSame('', FrontendSnippet::render());
    }

    public function test_renders_nothing_when_disabled(): void
    {
        config(['kilden.enabled' => false]);

        $this->assertSame('', FrontendSnippet::render());
    }

    public function test_wires_the_identity_endpoint_when_the_route_is_registered(): void
    {
        KildenRoutes::identity();
        // Ad-hoc registration in a test: real apps refresh name lookups when
        // the route files finish loading.
        \Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();

        $html = FrontendSnippet::render();

        $this->assertStringContainsString('getIdentityToken', $html);
        $this->assertStringContainsString('"/kilden/identity"', $html);
        $this->assertStringContainsString('X-XSRF-TOKEN', $html);
    }

    public function test_omits_the_identity_callback_without_the_route(): void
    {
        $this->assertStringNotContainsString('getIdentityToken', FrontendSnippet::render());
    }

    public function test_identifies_the_authenticated_user(): void
    {
        KildenRoutes::identity();
        $this->actingAs(new GenericUser(['id' => 4821]));

        $html = FrontendSnippet::render();

        $this->assertStringContainsString('kilden.identify("4821")', $html);
    }

    public function test_does_not_identify_guests(): void
    {
        $this->assertStringNotContainsString('kilden.identify', FrontendSnippet::render());
    }

    public function test_escapes_the_distinct_id_for_javascript(): void
    {
        $this->actingAs(new GenericUser(['id' => '</script><svg onload=x>']));

        $html = FrontendSnippet::render();

        $this->assertStringNotContainsString('</script><svg', $html);
        $this->assertStringContainsString('kilden.identify("\u003C/script\u003E\u003Csvg onload=x\u003E")', $html);
    }

    public function test_sets_api_host_only_when_it_differs_from_the_cloud_default(): void
    {
        $this->assertStringNotContainsString('apiHost', FrontendSnippet::render());

        config(['kilden.host' => 'https://ingest.example.com']);

        $this->assertStringContainsString('"apiHost":"https://ingest.example.com"', FrontendSnippet::render());
    }

    public function test_frontend_host_wins_over_the_server_host(): void
    {
        // The panel-style split: the server posts in-cluster, the browser
        // must use the public ingest host.
        config(['kilden.host' => 'http://capture:8080']);
        config(['kilden.frontend.host' => 'https://ingest.example.com']);

        $this->assertStringContainsString('"apiHost":"https://ingest.example.com"', FrontendSnippet::render());
        $this->assertStringNotContainsString('capture:8080', FrontendSnippet::render());
    }

    public function test_frontend_host_equal_to_cloud_default_omits_api_host(): void
    {
        config(['kilden.host' => 'http://capture:8080']);
        config(['kilden.frontend.host' => 'https://ingest.kilden.io']);

        $this->assertStringNotContainsString('apiHost', FrontendSnippet::render());
    }
}
