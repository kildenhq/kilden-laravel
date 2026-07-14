<?php

declare(strict_types=1);

namespace Kilden\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kilden\Client;

/**
 * Carries one SDK call to a queue worker. Serializes the call, never the
 * client: the worker resolves its own singleton and flushes immediately —
 * a queued event must not sit in a worker's memory waiting for flush_at.
 */
class SendKildenEvent implements ShouldQueue
{
    use Dispatchable;
    use Queueable;
    use SerializesModels;

    public string $method;

    /** @var list<mixed> */
    public array $arguments;

    /**
     * @param list<mixed> $arguments
     */
    public function __construct(string $method, array $arguments)
    {
        $this->method = $method;
        $this->arguments = $arguments;
    }

    public function handle(Client $client): void
    {
        $client->{$this->method}(...$this->arguments);
        $client->flush();
    }
}
