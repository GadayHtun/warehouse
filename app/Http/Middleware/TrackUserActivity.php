<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackUserActivity
{
    /**
     * Update last_active_at on every authenticated request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($user = $request->user()) {
            $user->last_active_at = now();
            $user->save();
        }

        return $next($request);
    }
}
