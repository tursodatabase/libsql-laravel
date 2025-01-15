<?php

declare(strict_types=1);

namespace Libsql\Laravel;

use Illuminate\Database\SQLiteConnection;

class LibsqlConnection extends SQLiteConnection
{
    #[\ReturnTypeWillChange]
    protected function getDefaultSchemaGrammar(): LibsqlSchemaGrammar
    {
        ($grammar = new LibsqlSchemaGrammar())->setConnection($this);
        return $this->withTablePrefix($grammar);
    }
}
