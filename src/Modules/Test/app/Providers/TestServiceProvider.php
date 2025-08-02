<?php

namespace Modules\Test\app\Providers;

use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Test';

    protected string $moduleNameLower = 'test';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
