<?php
declare(strict_types=1);

namespace App\Services\Integrations;

use RuntimeException;

/**
 * Deprecated stub: use Http::withRetry macro and listen for HttpRetryEvent.
 */
class HttpRetryMiddleware
{
    public function __construct()
    {
        throw new RuntimeException('HttpRetryMiddleware removed: use Http::withRetry and HttpRetryEvent instead.');
    }
}
