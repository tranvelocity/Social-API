<?php

namespace Modules\Like\app\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Like\app\Repositories\LikeRepository;
use Modules\Like\app\Repositories\LikeRepositoryInterface;

class LikeServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Like';

    protected string $moduleNameLower = 'like';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerTranslations();
        $this->app->bind(LikeRepositoryInterface::class, LikeRepository::class);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
        $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower . '.php')], 'config');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower);
    }
}
