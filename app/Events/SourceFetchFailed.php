<?php
declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Source;

class SourceFetchFailed
{
    use Dispatchable, SerializesModels;

    public Source $source;
    public string $errorMessage;
    public bool $exhausted;

    public function __construct(Source $source, string $errorMessage, bool $exhausted = false)
    {
        $this->source = $source;
        $this->errorMessage = $errorMessage;
        $this->exhausted = $exhausted;
    }
}
