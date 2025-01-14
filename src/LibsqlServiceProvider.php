<?php

declare(strict_types=1);

namespace Libsql\Laravel;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Database\Query\Builder;

class LibsqlServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        parent::boot();
        if (config('database.default') !== 'libsql') {
            return;
        }

        Blueprint::macro('vectorIndex', function ($column, $indexName) {
            /** @var Blueprint $this **/
            return DB::statement("CREATE INDEX {$indexName} ON {$this->table}(libsql_vector_idx({$column}))");
        });

        Builder::macro('nearest', function ($indexName, $vector, $limit = 10) {
            /** @var Builder $this **/
            return $this->joinSub(
                DB::table(DB::raw("vector_top_k('$indexName', '[" . implode(',', $vector) . "]', $limit)")),
                'v',
                "{$this->from}.rowid",
                '=',
                'v.id'
            );
        });
    }


    public function configurePackage(Package $package): void
    {
        $package->name('libsql-laravel');
    }

    public function register(): void
    {
        parent::register();
        $this->app->singleton('db.factory', function ($app) {
            return new class ($app) extends ConnectionFactory {
                protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
                {
                    return new LibsqlConnection(
                        function () use ($config) {
                            return new \Libsql\PDO(
                                database: $config["database"] ?? null,
                                password: $config["password"] ?? null,
                                options: $config,
                            );
                        },
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

                return new LibsqlConnection(
                    function () use ($config) {
                        return new \Libsql\PDO(
                            $config["database"] ?? null,
                            password: $config["password"] ?? null,
                            options: $config
                        );
                    },
                    database: $config["database"] ?? null,
                    config: $config,
                );
            });
        });
    }
}
