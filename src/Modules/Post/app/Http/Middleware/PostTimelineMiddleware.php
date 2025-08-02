<?php

namespace Modules\Post\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Role\app\Entities\Role;
use Modules\Role\app\Http\Middleware\RoleMiddleware;
use Modules\Session\App\Http\Middlewares\UserAuthenticationMiddleware;

/**
 * Middleware for handling post social requests.
 *
 * This middleware checks for the presence of authentication headers (`auth_id` and `user_ssid`).
 * If either of these headers is missing, it assigns a role of non-registered user to the request.
 * If both headers are present, it calls the `AuthenticateSession` and `RoleMiddleware` to handle the request.
 */
class PostSocialMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $authId = $request->header(config('api.auth.headers.auth_id'));
        $userSsid = $request->header(config('api.auth.headers.user_ssid'));

        // Check if authentication headers are missing
        if (is_null($authId) || is_null($userSsid)) {
            // Assign a role of non-registered user
            $request->merge(['role' => Role::nonRegisteredUser()->getRole()]);

            // Proceed to the next middleware or controller
            return $next($request);
        }

        //Call the AuthenticateSession and RoleMiddleware in sequence.
        $authenticateSessionMiddleware = App::make(UserAuthenticationMiddleware::class);
        $roleMiddleware = App::make(RoleMiddleware::class);

        // Call the handle method of AuthenticateSession
        return $authenticateSessionMiddleware->handle($request, function ($request) use ($roleMiddleware, $next) {
            // Call the handle method of RoleMiddleware
            return $roleMiddleware->handle($request, $next);
        });
    }
}
