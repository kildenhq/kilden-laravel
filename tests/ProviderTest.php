<?php

declare(strict_types=1);

namespace Kilden\Laravel\Tests;

use Kilden\Client;
use Kilden\IdentitySigner;
use Kilden\Laravel\Facades\Kilden;
use Kilden\Laravel\Jobs\SendKildenEvent;
use Illuminate\Support\Facades\Queue;

final class ProviderTest extends TestCase
{
    public function testClientIsASingleton(): void
    {
        $a = $this->app->make(Client::class);
        $b = $this->app->make(Client::class);

        self::assertSame($a, $b);
    }

    public function testSignerComesFromConfig(): void
    {
        $signer = $this->app->make(IdentitySigner::class);
        $token = $signer->sign('user_1');

        self::assertSame(3, count(explode('.', $token)));
        $header = json_decode((string) base64_decode(strtr(explode('.', $token)[0], '-_', '+/')), true);
        self::assertSame('k1', $header['kid']);
    }

    public function testSignerRequiresSecret(): void
    {
        config()->set('kilden.identity.secret', '');
        $this->app->forgetInstance(IdentitySigner::class);

        $this->expectException(\RuntimeException::class);
        $this->app->make(IdentitySigner::class);
    }

    public function testFacadeProxiesToManager(): void
    {
        // enabled=false: this must be a harmless no-op end to end.
        Kilden::track('user_1', 'noop_event');
        Kilden::flush();

        self::assertTrue(true);
    }

    public function testQueueModeDispatchesAJob(): void
    {
        config()->set('kilden.queue.enabled', true);
        Queue::fake();

        Kilden::track('user_1', 'queued_event', ['a' => 1]);

        Queue::assertPushed(SendKildenEvent::class, static function (SendKildenEvent $job): bool {
            return $job->method === 'track'
                && $job->arguments[0] === 'user_1'
                && $job->arguments[1] === 'queued_event'
                && isset($job->arguments[3]['timestamp']);
        });
    }

    public function testQueuedJobSendsThroughTheClient(): void
    {
        $job = new SendKildenEvent('track', ['user_1', 'from_worker', [], []]);
        $client = $this->app->make(Client::class);

        // enabled=false client: handle() must run without touching the network.
        $job->handle($client);

        self::assertSame(0, $client->droppedCount());
    }

    public function testFakeRecordsAndAsserts(): void
    {
        $fake = Kilden::fake();

        Kilden::track('user_1', 'purchase', ['revenue' => 10]);
        Kilden::identify('user_1', ['plan' => 'pro']);

        $fake->assertTracked('purchase', static function (array $call): bool {
            return $call['properties']['revenue'] === 10;
        });
        $fake->assertIdentified('user_1');

        $fake->flags['new_checkout'] = 'variant_b';
        self::assertTrue(Kilden::isEnabled('new_checkout', 'user_1'));
        self::assertSame('variant_b', Kilden::getFeatureFlag('new_checkout', 'user_1'));
    }
}
