<?php

declare(strict_types=1);

namespace Tranauth\Laravel\Api\AuditLog\Providers;

use Tranauth\Laravel\Api\AuditLog\Controllers\AuditLogController;
use Tranauth\Laravel\Api\AuditLog\Repositories\AuditLogRepository;
use Tranauth\Laravel\Api\AuditLog\Repositories\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuditLogRepositoryServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            AuditLogRepositoryInterface::class,
            AuditLogRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (app()->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__.'/../../../Foundation/Database/Migrations' => database_path('migrations'),
            ], 'auditlog-migrations');
        }

        $this->defineRoutes();
    }

    /**
     * Define the Audit log routes.
     *
     * @return void
     */
    protected function defineRoutes()
    {
        Route::group(['prefix' => '1'], function () {
            Route::get('/audit-logs', AuditLogController::class.'@index')
                ->middleware('api', 'auth.api')
                ->name('audit_logs.retrieval');
        });
    }

    /**
     * Register migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        return $this->loadMigrationsFrom(__DIR__.'/../../../Foundation/Database/Migrations');
    }
}
