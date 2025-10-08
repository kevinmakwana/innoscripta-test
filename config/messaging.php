<?php

return [
    'default' => env('MESSAGING_DRIVER', 'redis'),
    'dlq_prefix' => env('MESSAGING_DLQ_PREFIX', 'dlq.'),

    'brokers' => [
        'redis' => [
            'class' => \App\Services\Messaging\RedisMessageBroker::class,
        ],
        'rabbitmq' => [
            'class' => \App\Services\Messaging\RabbitMQMessageBroker::class,
            'config' => [
                'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                'port' => env('RABBITMQ_PORT', 5672),
                'user' => env('RABBITMQ_USER', 'guest'),
                'password' => env('RABBITMQ_PASSWORD', 'guest'),
                'exchange' => env('RABBITMQ_EXCHANGE', 'default_exchange'),
            ],
        ],
    ],
];
