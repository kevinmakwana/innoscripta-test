<?php

namespace App\Providers;

use App\Events\HttpRetryEvent;
use App\Events\SourceFetchFailed;
use App\Listeners\RecordHttpRetryMetrics;
use App\Listeners\RecordSourceFetchFailureMetrics;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected array $listen = [
        HttpRetryEvent::class => [
            RecordHttpRetryMetrics::class,
        ],
        SourceFetchFailed::class => [
            RecordSourceFetchFailureMetrics::class,
        ],
    ];

    public function register(): void
    {
        // listeners are auto-discovered via the $listen property
    }
}
