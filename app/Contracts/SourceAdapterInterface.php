<?php
declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

interface SourceAdapterInterface
{
    /**
     * Fetch top headlines or equivalent items from the source.
     *
     * @param array<string,mixed> $params
     * @return \Illuminate\Support\Collection<int, array<string,mixed>>
     */
    public function fetchTopHeadlines(array $params = []): Collection;
}
