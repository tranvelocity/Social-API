<?php

declare(strict_types=1);

namespace Modules\Notification;

use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Notification';
    protected string $moduleNameLower = 'notification';

    /**
     * @return void
     */
    public function register(): void
    {
        $this->registerConfig();
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configFile = __DIR__.'/config/config.php';

        if (file_exists($configFile)) {
            $this->mergeConfigFrom($configFile, $this->moduleNameLower);
        }
    }
}
