<?php

namespace Modules\PingPong\Providers;

use Illuminate\Support\ServiceProvider;

class PingPongServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'PingPong';

    /**
     * @var string $moduleNameLower
     */
    protected string $moduleNameLower = 'pingpong';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
