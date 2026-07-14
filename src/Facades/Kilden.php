<?php

declare(strict_types=1);

namespace Kilden\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Kilden\Laravel\KildenManager;
use Kilden\Laravel\Testing\KildenFake;

/**
 * @method static void track(string $distinctId, string $event, array $properties = [], array $opts = [])
 * @method static void identify(string $distinctId, array $traits = [], array $opts = [])
 * @method static void alias(string $previousId, string $distinctId)
 * @method static bool isEnabled(string $flagKey, string $distinctId, array $opts = [])
 * @method static mixed getFeatureFlag(string $flagKey, string $distinctId, array $opts = [])
 * @method static string identityToken(string $distinctId, array $traits = [])
 * @method static void flush()
 * @method static void close()
 *
 * @see KildenManager
 */
class Kilden extends Facade
{
    public static function fake(): KildenFake
    {
        $fake = new KildenFake();
        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return KildenManager::class;
    }
}
