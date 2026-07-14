<?php

declare(strict_types=1);

namespace Kilden\Laravel\Tests;

use Illuminate\Foundation\Auth\User;
use Kilden\Laravel\KildenRoutes;

final class IdentityRouteTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        KildenRoutes::identity();
    }

    public function testRequiresAuthentication(): void
    {
        config()->set('kilden.identity.middleware', ['web', 'auth']);

        $response = $this->postJson('/kilden/identity');

        $response->assertStatus(401);
    }

    public function testSignsForTheAuthenticatedUser(): void
    {
        KildenRoutes::traitsUsing(static function ($user): array {
            return ['plan' => 'pro'];
        });

        $user = new class extends User {
            protected $table = 'users';
        };
        $user->forceFill(['id' => 42]);

        $response = $this->actingAs($user)->postJson('/kilden/identity');

        $response->assertOk();
        $response->assertJsonPath('distinct_id', '42');
        $response->assertJsonPath('traits.plan', 'pro');

        $token = $response->json('token');
        [$header64, $payload64] = explode('.', $token);
        $payload = json_decode((string) base64_decode(strtr($payload64, '-_', '+/')), true);
        self::assertSame('42', $payload['sub']);
        self::assertSame(['plan' => 'pro'], $payload['traits']);
        self::assertSame($payload['iat'] + 3600, $payload['exp']);

        KildenRoutes::traitsUsing(static function ($user): array {
            return [];
        });
    }
}
