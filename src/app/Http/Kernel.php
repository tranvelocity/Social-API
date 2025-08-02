<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Modules\Core\app\Http\Middlewares\ApiAuthentication;
use Modules\Post\app\Http\Middleware\PostSocialMiddleware;
use Modules\Role\app\Http\Middleware\RoleMiddleware;
use Modules\Session\App\Http\Middlewares\UserAuthenticationMiddleware;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'api' => [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth.api' => ApiAuthentication::class,
        'post_social' => PostSocialMiddleware::class,
        'auth.session' => UserAuthenticationMiddleware::class,
        'role' => RoleMiddleware::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
    ];
}
