<?php

declare(strict_types=1);

namespace Kilden\Laravel;

use Illuminate\Support\ServiceProvider;
use Kilden\Client;
use Kilden\IdentitySigner;

class KildenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/kilden.php', 'kilden');

        $this->app->singleton(Client::class, function (): Client {
            $writeKey = (string) config('kilden.write_key');
            $enabled = (bool) config('kilden.enabled', true) && $writeKey !== '';

            /** @var array<string, mixed> $options */
            $options = (array) config('kilden.options', []);
            $options['host'] = (string) config('kilden.host', 'https://ingest.kilden.io');
            $options['debug'] = (bool) config('kilden.debug', false);
            $options['enabled'] = $enabled;

            // A disabled client accepts any key; keep boot working when the
            // env var is absent (e.g. CI) instead of failing construction.
            return new Client($writeKey !== '' ? $writeKey : 'sk_disabled', $options);
        });

        $this->app->singleton(IdentitySigner::class, function (): IdentitySigner {
            $secret = (string) config('kilden.identity.secret');
            if ($secret === '') {
                throw new \RuntimeException(
                    'kilden.identity.secret is not set. Add KILDEN_IDENTITY_SECRET to your .env to sign identity tokens.'
                );
            }

            return new IdentitySigner($secret, ['kid' => (string) config('kilden.identity.kid', 'k1')]);
        });

        $this->app->singleton(KildenManager::class, function ($app): KildenManager {
            return new KildenManager($app);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/kilden.php' => config_path('kilden.php'),
            ], 'kilden-config');
        }

        // Long-running runtimes (Octane, queue workers) never hit PHP's
        // shutdown handler between requests; terminating() drains per request.
        if (method_exists($this->app, 'terminating')) {
            $this->app->terminating(function (): void {
                if ($this->app->resolved(Client::class)) {
                    $this->app->make(Client::class)->flush();
                }
            });
        }
    }
}
