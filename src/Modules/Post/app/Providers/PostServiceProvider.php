<?php

namespace Modules\Post\app\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Post\app\Repositories\PostRepository;
use Modules\Post\app\Repositories\PostRepositoryInterface;
use Modules\Post\app\Repositories\PostSocialRepository;
use Modules\Post\app\Repositories\PostSocialRepositoryInterface;

class PostServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Post';

    protected string $moduleNameLower = 'post';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        //register repositories
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(PostSocialRepositoryInterface::class, PostSocialRepository::class);
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
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower . '.php')], 'config');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
