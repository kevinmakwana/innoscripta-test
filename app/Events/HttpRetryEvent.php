<?php
declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HttpRetryEvent
{
    use Dispatchable, SerializesModels;

    public string $uri;
    public int $attempt;
    public ?int $statusCode;
    public ?string $errorMessage;
    public int $elapsedMs;
    public bool $exhausted;

    public function __construct(string $uri, int $attempt, ?int $statusCode, ?string $errorMessage, int $elapsedMs, bool $exhausted = false)
    {
        $this->uri = $uri;
        $this->attempt = $attempt;
        $this->statusCode = $statusCode;
        $this->errorMessage = $errorMessage;
        $this->elapsedMs = $elapsedMs;
        $this->exhausted = $exhausted;
    }
}
