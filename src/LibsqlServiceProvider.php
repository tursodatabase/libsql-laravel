<?php
declare(strict_types=1);

declare(strict_types=1);

namespace Libsql\Laravel;

use Libsql\Laravel\Vector\VectorMacro;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Database\DatabaseManager;
use Libsql\Laravel\Database\LibsqlConnector;
use Libsql\Laravel\Database\LibsqlConnection;
use Libsql\Laravel\Database\LibsqlConnectionFactory;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LibsqlServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        parent::boot();
        if (config('database.default') !== 'libsql') {
            return;
        }
    }

    public function configurePackage(Package $package): void
    {
        $package->name('libsql-laravel');
    }

    public function register(): void
    {
        parent::register();

        VectorMacro::create();

        $this->app->singleton('db.factory', function ($app) {
            return new LibsqlConnectionFactory($app);
        });

        $this->app->scoped(LibsqlManager::class, function ($app) {
            return new LibsqlManager(config('database.connections.libsql'));
        });

        $this->app->resolving('db', function (DatabaseManager $db) {
            $db->extend('libsql', function ($config, $name) {
                $config = config('database.connections.libsql');
                $config['name'] = $name;
                if (!isset($config['driver'])) {
                    $config['driver'] = 'libsql';
                }

                $connector = new LibsqlConnector();
                $db = $connector->connect($config);

                $connection = new LibsqlConnection($db, $config['database'] ?? ':memory:', $config['prefix'], $config);
                app()->instance(LibsqlConnection::class, $connection);

                $connection->createReadPdo($config);

                return $connection;
            });
        });
    }
}
