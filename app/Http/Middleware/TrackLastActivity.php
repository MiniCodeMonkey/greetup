<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackLastActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Perform actions after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($request->user()) {
            DB::table('users')
                ->where('id', $request->user()->id)
                ->update(['last_active_at' => now()]);
        }
    }
}
