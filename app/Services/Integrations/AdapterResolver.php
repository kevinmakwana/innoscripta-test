<?php
declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Source;
use App\Contracts\SourceAdapterInterface;

class AdapterResolver
{
    public function resolveForSource(Source $source): ?SourceAdapterInterface
    {
        return AdapterFactory::forSource($source);
    }
}