<?php

declare(strict_types=1);

namespace Modules\AWS\app\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\AWS\S3\Adapters\S3MultipartUploader;
use Modules\AWS\S3\Services\S3FileService;
use Modules\Core\app\Services\FileServiceInterface;

class AWSServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'AWS';
    protected string $moduleNameLower = 'aws';

    /**
     * @return void
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->bind(FileServiceInterface::class, S3FileService::class);

        // Bind the S3MultipartUploader with a closure that resolves its dependencies
        $this->app->bind(S3MultipartUploader::class, function () {
            return new S3MultipartUploader(Config::get('aws.aws_default_region', 'ap-northeast-1'));
        });
    }

    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower . '.php')], 'config');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower);
    }
}
