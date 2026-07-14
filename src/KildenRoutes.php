<?php

declare(strict_types=1);

namespace Kilden\Laravel;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;
use Kilden\Laravel\Http\IdentityController;

/**
 * One line in routes/web.php:
 *
 *     KildenRoutes::identity();
 *
 * registers POST /kilden/identity behind the configured auth middleware.
 */
class KildenRoutes
{
    /** @var Closure(Authenticatable): array<string, mixed>|null */
    protected static ?Closure $traitsResolver = null;

    public static function identity(string $path = '/kilden/identity'): void
    {
        /** @var list<string> $middleware */
        $middleware = (array) config('kilden.identity.middleware', ['web', 'auth']);

        Route::post($path, IdentityController::class)
            ->middleware($middleware)
            ->name('kilden.identity');
    }

    /**
     * Optional: choose which user attributes travel as signed traits.
     *
     *     KildenRoutes::traitsUsing(fn ($user) => ['plan' => $user->plan]);
     */
    public static function traitsUsing(Closure $resolver): void
    {
        static::$traitsResolver = $resolver;
    }

    /**
     * @return array<string, mixed>
     */
    public static function traitsFor(Authenticatable $user): array
    {
        if (static::$traitsResolver === null) {
            return [];
        }

        return (static::$traitsResolver)($user);
    }
}
