<?php

namespace Solivellaluisaberto\PayKit\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Solivellaluisaberto\PayKit\PayKitServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Configuraciones adicionales para tests
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            PayKitServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'PayKit' => \Solivellaluisaberto\PayKit\Facades\PayKit::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Configuración del entorno de pruebas
        $app['config']->set('database.default', 'testing');
    }
}

