<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\RateLimiter;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Testes de auth fazem login com o mesmo email/IP; sem limpar, o rate
        // limiter (3/min por IP+email) bloqueia testes posteriores no mesmo processo.
        foreach (['login', 'register', 'auth', 'sensitive', 'search', 'recalculate', 'export', 'grade', 'generate', 'review', 'health'] as $limiter) {
            RateLimiter::clear($limiter);
        }
    }

    /**
     * Evita que o RequestGuard do Sanctum reutilize o utilizador em cache entre pedidos
     * no mesmo processo (token revogado no BD mas ainda "autenticado" na instância do guard).
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        if (isset($this->app['auth'])) {
            $this->app['auth']->forgetGuards();
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
