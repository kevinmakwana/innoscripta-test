<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Schedule the articles fetch command hourly. Use withoutOverlapping
        // and onOneServer to avoid concurrent runs in clustered deployments.
        // runInBackground is used so the scheduler doesn't block while the
        // job dispatches work to the queue worker.
        $schedule->command('articles:fetch')
            ->name('articles:fetch')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // Additional console commands for messaging (publish/consume) live
        // under app/Console/Commands and are auto-loaded by the above call.

        require base_path('routes/console.php');
    }
}
