<?php

namespace Modules\Media\app\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Modules\Media\app\Console\DeleteJunkMediaFileCommand;
use Modules\Media\app\Models\Media;
use Modules\Media\app\Repositories\MediaRepository;
use Modules\Media\app\Repositories\MediaRepositoryInterface;

class MediaServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Media';

    protected string $moduleNameLower = 'media';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        $this->app->bind(MediaRepositoryInterface::class, MediaRepository::class);

        Validator::extend(Media::VIDEO_TYPE, function ($attribute, $value, $parameters, $validator) {
            return str_starts_with($value->getMimeType(), Media::VIDEO_TYPE . '/');
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        //register repositories
        $this->app->bind(MediaRepositoryInterface::class, MediaRepository::class);
    }

    /**
     * Register commands in the format of Command::class.
     */
    protected function registerCommands(): void
    {
        $this->commands([DeleteJunkMediaFileCommand::class]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->commands([DeleteJunkMediaFileCommand::class]);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
        }
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
