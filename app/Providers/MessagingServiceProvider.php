<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind a MessageBrokerInterface here so projects can override this
        // provider to register different broker implementations per environment.
        $this->app->bind(\App\Contracts\MessageBrokerInterface::class, function ($app) {
            // For testing, always use RedisMessageBroker
            return new \App\Services\Messaging\RedisMessageBroker();
        });
    }

    public function boot(): void
    {
        // no-op
    }
}
