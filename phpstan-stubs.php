<?php

namespace Illuminate\Support\Facades {
    class Http {
        public static function withRetry(?int $attempts = null, ?int $baseSleepMs = null, ?int $maxSleepMs = null) {}
    }

    class Redis {
        public static function incr(string $key) {}
        public static function expire(string $key, int $seconds) {}
        public static function set(string $key, $value, $expire = null) {}
    }
}

// Eloquent helper stubs (minimal)
namespace Illuminate\Database\Eloquent {
    class Model {}
}

// No global helpers — rely on framework helpers during analysis.
