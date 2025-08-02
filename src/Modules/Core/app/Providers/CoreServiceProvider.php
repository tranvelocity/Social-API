<?php

declare(strict_types=1);

namespace Modules\Core\app\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Comment\app\Repositories\CommentRepository;
use Modules\Comment\app\Repositories\CommentRepositoryInterface;
use Modules\Core\app\Repositories\CacheRepository;
use Modules\Core\app\Repositories\CacheRepositoryInterface;
use Modules\Core\app\Repositories\Repository;
use Modules\Core\app\Repositories\RepositoryInterface;

class CoreServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Core';
    protected string $moduleNameLower = 'core';


    public function boot(): void
    {
        $this->register();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }


    /**
     * @return void
     */
    public function register(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->app->bind(RepositoryInterface::class, Repository::class);
        $this->app->bind(CacheRepositoryInterface::class, CacheRepository::class);
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
}
