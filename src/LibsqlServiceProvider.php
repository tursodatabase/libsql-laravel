<?php

namespace Turso\LibsqlLaravel;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\SQLiteConnection;
use Spatie\LaravelPackageTools\Package;
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
        $this->app->singleton('db.factory', function ($app) {
            return new class($app) extends ConnectionFactory {
                protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
                {
                    $connection = function () use ($config) {
                        return new \Libsql\PDO(
                            $config["database"] ?? '',
                            password: $config["password"] ?? '',
                            options: $config,
                        );
                    };

                    var_dump($config);

                    return new SQLiteConnection(
                        $connection,
                        database: $config["database"] ?? '',
                        config: $config,
                    );
                }
            };
        });

        $this->app->resolving('db', function (DatabaseManager $db) {
            $db->extend('libsql', function ($config, $name) {
                $config = config('database.connections.libsql');
                $config['name'] = $name;
                if (!isset($config['driver'])) {
                    $config['driver'] = 'libsql';
                }

                return new SQLiteConnection(
                    function () use ($config) {
                        return new \Libsql\PDO(
                            $config["database"] ?? '',
                            password: $config["password"] ?? '',
                            options: $config
                        );
                    },
                    database: $config["database"],
                    config: $config,
                );
            });
        });
    }
}
