<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    /**
     * Restrict route access by user role.
     * Accepts one or more roles: 'admin', 'supervisor', 'agent'.
     *
     * Usage in routes: ->middleware('role:supervisor,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!in_array($user->role, $roles, true)) {
            Log::channel('audit')->warning('role_authorization_failed', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'required_roles' => $roles,
                'uri' => $request->getRequestUri(),
            ]);

            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
