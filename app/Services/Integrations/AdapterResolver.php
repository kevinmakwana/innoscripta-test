<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Contracts\SourceAdapterInterface;
use App\Models\Source;

class AdapterResolver
{
    public function resolveForSource(Source $source): ?SourceAdapterInterface
    {
        return AdapterFactory::forSource($source);
    }
}
