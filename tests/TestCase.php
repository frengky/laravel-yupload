<?php

namespace Frengky\Yupload\Tests;

use Frengky\Yupload\UploadServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(
            realpath(__DIR__.'/database/migrations')
        );
        $this->withFactories(
            realpath(__DIR__.'/database/factories')
        );

        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('yupload.storage_disk', 'testing');
    }

    /**
     * Define package service provider
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            UploadServiceProvider::class
        ];
    }

    /**
     * Get application timezone.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return string|null
     */
    protected function getApplicationTimezone($app)
    {
        return 'Asia/Jakarta';
    }
}
