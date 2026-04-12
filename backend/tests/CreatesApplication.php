<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // #region agent log
        $logFile = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'debug-32ae8a.log';
        @file_put_contents(
            $logFile,
            json_encode([
                'sessionId' => '32ae8a',
                'hypothesisId' => 'H1',
                'location' => 'CreatesApplication.php:createApplication',
                'message' => 'resolved db/redis after bootstrap',
                'data' => [
                    'DB_CONNECTION' => (string) config('database.default'),
                    'DB_HOST' => (string) config('database.connections.pgsql.host'),
                    'DB_DATABASE' => (string) config('database.connections.pgsql.database'),
                    'REDIS_HOST' => (string) config('database.redis.default.host'),
                    'redis_password_configured' => (bool) config('database.redis.default.password'),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
            FILE_APPEND | LOCK_EX
        );
        // #endregion

        return $app;
    }
}
