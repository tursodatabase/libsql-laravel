<?php

declare(strict_types=1);

namespace Libsql\Laravel;

use BadMethodCallException;
use Illuminate\Support\Collection;
use Libsql\Laravel\Database\LibsqlDatabase;

class LibsqlManager
{
    protected LibsqlDatabase $client;

    protected Collection $config;

    public function __construct(array $config = [])
    {
        $this->config = new Collection($config);
        $this->client = new LibSQLDatabase($config);
    }

    public function __call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this->client, $method)) {
            throw new BadMethodCallException('Call to undefined method ' . static::class . '::' . $method . '()');
        }

        return $this->client->$method(...$arguments);
    }
}
