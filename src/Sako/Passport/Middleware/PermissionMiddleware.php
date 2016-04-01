<?php

namespace Sako\Passport\Middleware;

use Route;
use Closure;
use Sako\Passport\Exceptions\PermissionDenied;

class PermissionMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws ClassNotFoundException
     */
    public function handle($request, Closure $next)
    {
        $user      = $request->user();
        $routeName = Route::currentRouteName();

        if ( $user->is_superuser || ($user && $user->hasPermission($routeName))) {
            return $next($request);
        }

        throw new PermissionDenied(config('passport.messages.insufficient_permission'), 403);
    }
}
