<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\HeaderBag;

it('rejects a login request without a csrf token', function () {
    $middleware = new class(app(), app('encrypter')) extends PreventRequestForgery
    {
        protected function runningUnitTests()
        {
            return false;
        }
    };

    $request = Request::create('/login', 'POST');
    $request->headers = new HeaderBag([
        'Sec-Fetch-Site' => 'cross-site',
    ]);
    $request->setLaravelSession(app('session')->driver());

    try {
        $middleware->handle($request, fn () => response('unexpected'));
        test()->fail('The request was not rejected.');
    } catch (TokenMismatchException) {
        expect(true)->toBeTrue();
    }
});
