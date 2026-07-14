<?php

declare(strict_types=1);

namespace Kilden\Laravel\Testing;

use PHPUnit\Framework\Assert;

/**
 * Swapped in by Kilden::fake(): records calls, sends nothing, asserts.
 */
class KildenFake
{
    /** @var list<array{distinct_id: string, event: string, properties: array<string, mixed>, opts: array<string, mixed>}> */
    public array $tracked = [];

    /** @var list<array{distinct_id: string, traits: array<string, mixed>, opts: array<string, mixed>}> */
    public array $identified = [];

    /** @var list<array{previous_id: string, distinct_id: string}> */
    public array $aliased = [];

    /** @var array<string, mixed> flag_key => forced value */
    public array $flags = [];

    /**
     * @param array<string, mixed> $properties
     * @param array<string, mixed> $opts
     */
    public function track(string $distinctId, string $event, array $properties = [], array $opts = []): void
    {
        $this->tracked[] = ['distinct_id' => $distinctId, 'event' => $event, 'properties' => $properties, 'opts' => $opts];
    }

    /**
     * @param array<string, mixed> $traits
     * @param array<string, mixed> $opts
     */
    public function identify(string $distinctId, array $traits = [], array $opts = []): void
    {
        $this->identified[] = ['distinct_id' => $distinctId, 'traits' => $traits, 'opts' => $opts];
    }

    public function alias(string $previousId, string $distinctId): void
    {
        $this->aliased[] = ['previous_id' => $previousId, 'distinct_id' => $distinctId];
    }

    /**
     * Force flag values for the test: $fake->flags['new_checkout'] = true.
     *
     * @param array<string, mixed> $opts
     */
    public function isEnabled(string $flagKey, string $distinctId, array $opts = []): bool
    {
        $value = $this->getFeatureFlag($flagKey, $distinctId, $opts);

        return $value === true || is_string($value);
    }

    /**
     * @param array<string, mixed> $opts
     */
    public function getFeatureFlag(string $flagKey, string $distinctId, array $opts = []): mixed
    {
        if (array_key_exists($flagKey, $this->flags)) {
            return $this->flags[$flagKey];
        }

        return array_key_exists('default', $opts) ? $opts['default'] : false;
    }

    /**
     * @param array<string, mixed> $traits
     */
    public function identityToken(string $distinctId, array $traits = []): string
    {
        return 'fake-token-for-' . $distinctId;
    }

    public function flush(): void
    {
    }

    public function close(): void
    {
    }

    public function assertTracked(string $event, ?callable $callback = null): void
    {
        $matching = array_filter($this->tracked, static function (array $call) use ($event, $callback): bool {
            if ($call['event'] !== $event) {
                return false;
            }

            return $callback === null || $callback($call) === true;
        });

        Assert::assertNotEmpty($matching, "Expected event '{$event}' was not tracked.");
    }

    public function assertNothingTracked(): void
    {
        Assert::assertSame([], $this->tracked, 'Expected no tracked events.');
    }

    public function assertIdentified(string $distinctId): void
    {
        $matching = array_filter($this->identified, static function (array $call) use ($distinctId): bool {
            return $call['distinct_id'] === $distinctId;
        });

        Assert::assertNotEmpty($matching, "Expected identify() for '{$distinctId}'.");
    }
}
