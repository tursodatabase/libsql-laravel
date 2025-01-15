<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

class LibsqlConnector
{
    /**
     * Establish a database connection.
     */
    public function connect(array $config): LibsqlDatabase
    {
        return new LibsqlDatabase($config);
    }
}
