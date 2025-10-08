<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Contracts\SourceAdapterInterface;
use App\Models\Source;

/**
 * Simple factory that returns an adapter instance for a given Source model.
 * Returns null when no adapter is available for the provided source slug.
 */
class AdapterFactory
{
    public static function forSource(Source $source): ?SourceAdapterInterface
    {
        // If the source has an explicit adapter class stored in the DB, prefer
        // to resolve it from the container. This allows tests to set
        // `adapter_class` on the Source and have the adapter instantiated in
        // the worker process (avoiding passing mocks across process
        // boundaries).
        if (! empty($source->adapter_class) && is_string($source->adapter_class) && class_exists($source->adapter_class)) {
            try {
                $instance = app($source->adapter_class);
                if ($instance instanceof SourceAdapterInterface) {
                    return $instance;
                }
            } catch (\Throwable $_) {
                // ignore resolution errors and fall back to slug mapping
            }
        }

        return match ($source->slug) {
            'newsapi' => new NewsApiAdapter,
            'theguardian' => new GuardianAdapter,
            'nytimes' => new NytAdapter,
            default => null,
        };
    }
}
