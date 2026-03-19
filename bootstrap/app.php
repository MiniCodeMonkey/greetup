<?php

use App\Http\Middleware\EnsureAccountNotSuspended;
use App\Http\Middleware\EnsureGroupMember;
use App\Http\Middleware\EnsureGroupRole;
use App\Http\Middleware\TrackLastActivity;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');

        $middleware->appendToGroup('web', TrackLastActivity::class);

        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'notSuspended' => EnsureAccountNotSuspended::class,
            'groupMember' => EnsureGroupMember::class,
            'groupRole' => EnsureGroupRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
