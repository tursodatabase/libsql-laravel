<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;

class LibsqlConnectionFactory extends ConnectionFactory
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        $port = isset($config['port']) ? ":{$config['port']}" : '';
        $config['url'] = "{$config['driver']}://{$config['host']}{$port}";
        $config['driver'] = 'libsql';

        $connection = function () use ($config) {
            return new LibsqlDatabase($config);
        };

        return new LibsqlConnection($connection(), $database, $prefix, $config);
    }

    public function createConnector(array $config)
    {
        //
    }
}
