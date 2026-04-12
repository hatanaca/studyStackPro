<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SetUserTimezone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class SetUserTimezoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sets_timezone_from_authenticated_user(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->timezone = 'America/Sao_Paulo';

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new SetUserTimezone;
        $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('America/Sao_Paulo', config('app.timezone'));
    }

    public function test_does_not_change_timezone_for_guest(): void
    {
        $originalTimezone = config('app.timezone');

        $request = Request::create('/test', 'GET');

        $middleware = new SetUserTimezone;
        $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals($originalTimezone, config('app.timezone'));
    }

    public function test_does_not_change_timezone_when_user_has_no_timezone(): void
    {
        $originalTimezone = config('app.timezone');

        $user = Mockery::mock(User::class)->makePartial();
        $user->timezone = null;

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new SetUserTimezone;
        $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals($originalTimezone, config('app.timezone'));
    }
}
