<?php

namespace Modules\User\app\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\User\app\Repositories\UserRepository;
use Modules\User\app\Repositories\UserRepositoryInterface;

class UserServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'User';
    protected string $moduleNameLower = 'user';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    public function register(): void
    {
        // Register additional services if needed
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
