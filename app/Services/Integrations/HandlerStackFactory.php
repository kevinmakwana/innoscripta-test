<?php

namespace App\Services\Integrations;

use RuntimeException;

class HandlerStackFactory
{
    /**
     * Former factory entrypoint. Kept for backward compatibility but removed.
     *
     * @param  mixed  ...$args
     */
    public function create(...$args): void
    {
        throw new RuntimeException('HandlerStackFactory removed: use Http::withRetry macro instead.');
    }
}
