<?php

namespace Frengky\Yupload;

use Illuminate\Support\ServiceProvider;

class UploadServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(realpath(__DIR__.'/../migrations'));

        $this->publishes([
            realpath(__DIR__.'/../config/yupload.php') => config_path('yupload.php')
        ], 'config');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            realpath(__DIR__.'/../config/yupload.php'), 'yupload'
        );
    }
}