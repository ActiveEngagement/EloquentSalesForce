<?php

namespace Lester\EloquentSalesForce\Tests;

use Lester\EloquentSalesForce\ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('logging.default', false);
        $app['config']->set('eloquent_sf.logging', false);
        $app['config']->set('eloquent_sf.forrest.authentication', 'UserPassword');
        $app['config']->set('eloquent_sf.forrest.storage.type', 'cache');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('database.connections.soql', [
            'driver' => 'soql',
            'database' => null,
            'consumerKey' => 'test-key',
            'consumerSecret' => 'test-secret',
            'callbackURI' => 'https://test.example.com/callback',
            'loginURL' => 'https://login.salesforce.com',
            'username' => 'test@test.com',
            'password' => 'testpass',
        ]);
    }
}
