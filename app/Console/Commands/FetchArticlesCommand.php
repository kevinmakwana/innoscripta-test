<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\FetchSourceJob;
use App\Models\Source;
use Illuminate\Console\Command;

class FetchArticlesCommand extends Command
{
    protected $signature = 'articles:fetch {--source=*}';

    protected $description = 'Fetch articles from configured sources';

    public function handle(): int
    {
        $sources = Source::query()->where('enabled', true);

        if ($this->option('source')) {
            $sources->whereIn('slug', $this->option('source'));
        }

        $sources = $sources->get();

        if ($sources->isEmpty()) {
            $this->info('No sources enabled.');

            return 0;
        }

        foreach ($sources as $source) {
            dispatch(new FetchSourceJob($source));
        }

        $this->info('Fetch jobs dispatched.');

        return 0;
    }
}
