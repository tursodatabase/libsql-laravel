<?php

declare(strict_types=1);

namespace Libsql\Laravel;

use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Fluent;

class LibsqlSchemaGrammar extends SQLiteGrammar {
    protected function typeVector(Fluent $column): string {
        if (isset($column->dimensions) && $column->dimensions !== '') {
            return "F32_BLOB({$column->dimensions})";
        }

        throw new RuntimeException('Dimension must be set for vector embedding');
    }
}
