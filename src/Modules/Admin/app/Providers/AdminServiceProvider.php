<?php

namespace Modules\Admin\app\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Admin\app\Repositories\AdminRepository;
use Modules\Admin\app\Repositories\AdminRepositoryInterface;

class AdminServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Admin';
    protected string $moduleNameLower = 'admin';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
    }

    public function register(): void
    {
        // Register RouteServiceProvider if you add it
    }

    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
        $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower);
    }
}
