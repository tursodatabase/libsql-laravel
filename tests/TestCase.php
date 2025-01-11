<?php

namespace Libsql\Laravel\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Libsql\Laravel\LibsqlServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Libsql\Laravel\TursoLaravelServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Libsql\\Laravel\\Tests\\Fixtures\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LibsqlServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.connections.libsql', [
            'driver'                  => 'libsql',
            'url' => "http://127.0.0.1:8080",
            'password' => "your-access-token",
        ]);
        config()->set('database.default', 'libsql');
        config()->set('queue.default', 'sync');
    }
}
