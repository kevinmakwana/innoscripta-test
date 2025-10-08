<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Mock Redis facade to avoid requiring phpredis in local CLI
\Illuminate\Support\Facades\Redis::shouldReceive('rpush')->andReturnUsing(function ($destination, $payload) {
	// store in simple static
	static $store = [];
	$store[$destination][] = is_string($payload) ? $payload : (string) json_encode($payload);
	return true;
});

\Illuminate\Support\Facades\Redis::shouldReceive('rpop')->andReturnUsing(function ($destination) {
	static $store = [];
	if (empty($store[$destination])) {
		return null;
	}
	return array_pop($store[$destination]);
});

\Illuminate\Support\Facades\Redis::shouldReceive('lpush')->andReturnTrue();
\Illuminate\Support\Facades\Redis::shouldReceive('get')->andReturnNull();
\Illuminate\Support\Facades\Redis::shouldReceive('setex')->andReturnTrue();

echo "Calling publish\n";
$ret = $kernel->call('messaging:publish-sample');
echo "Publish exit: {$ret}\n";

echo "Calling consume\n";
$ret2 = $kernel->call('messaging:consume', ['--limit' => 1]);
echo "Consume exit: {$ret2}\n";

echo "Done\n";
