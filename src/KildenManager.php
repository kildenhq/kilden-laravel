<?php

declare(strict_types=1);

namespace Kilden\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Kilden\Client;
use Kilden\IdentitySigner;
use Kilden\Laravel\Jobs\SendKildenEvent;

/**
 * The facade root. Queue-aware: with kilden.queue.enabled, event calls
 * dispatch a job instead of touching the network inline; flag reads are
 * always inline (the caller needs the answer now).
 */
class KildenManager
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param array<string, mixed> $properties
     * @param array<string, mixed> $opts
     */
    public function track(string $distinctId, string $event, array $properties = [], array $opts = []): void
    {
        $this->dispatch('track', [$distinctId, $event, $properties, $this->withTimestamp($opts)]);
    }

    /**
     * @param array<string, mixed> $traits
     * @param array<string, mixed> $opts
     */
    public function identify(string $distinctId, array $traits = [], array $opts = []): void
    {
        $this->dispatch('identify', [$distinctId, $traits, $this->withTimestamp($opts)]);
    }

    public function alias(string $previousId, string $distinctId): void
    {
        $this->dispatch('alias', [$previousId, $distinctId]);
    }

    /**
     * @param array<string, mixed> $opts
     */
    public function isEnabled(string $flagKey, string $distinctId, array $opts = []): bool
    {
        return $this->client()->isEnabled($flagKey, $distinctId, $opts);
    }

    /**
     * @param array<string, mixed> $opts
     */
    public function getFeatureFlag(string $flagKey, string $distinctId, array $opts = []): mixed
    {
        return $this->client()->getFeatureFlag($flagKey, $distinctId, $opts);
    }

    public function flush(): void
    {
        $this->client()->flush();
    }

    public function close(): void
    {
        $this->client()->close();
    }

    /**
     * Signs an identity token for an authenticated user id.
     *
     * @param array<string, mixed> $traits
     */
    public function identityToken(string $distinctId, array $traits = []): string
    {
        $signer = $this->app->make(IdentitySigner::class);
        $ttl = (int) config('kilden.identity.ttl', 3600);

        return $signer->sign($distinctId, ['ttl' => $ttl, 'traits' => $traits]);
    }

    public function client(): Client
    {
        return $this->app->make(Client::class);
    }

    /**
     * @param list<mixed> $arguments
     */
    protected function dispatch(string $method, array $arguments): void
    {
        if (!(bool) config('kilden.queue.enabled', false)) {
            $this->client()->{$method}(...$arguments);

            return;
        }

        $job = new SendKildenEvent($method, $arguments);
        if (($connection = config('kilden.queue.connection')) !== null) {
            $job->onConnection($connection);
        }
        if (($queue = config('kilden.queue.queue')) !== null) {
            $job->onQueue($queue);
        }
        dispatch($job);
    }

    /**
     * Queued jobs run later; the event must carry the time of the call, not
     * of the worker pickup.
     *
     * @param array<string, mixed> $opts
     * @return array<string, mixed>
     */
    protected function withTimestamp(array $opts): array
    {
        if ((bool) config('kilden.queue.enabled', false) && !array_key_exists('timestamp', $opts)) {
            $opts['timestamp'] = now('UTC')->format('Y-m-d\TH:i:s.v\Z');
        }

        return $opts;
    }
}
