<?php
declare(strict_types=1);

namespace Libsql\Laravel\Vector;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

class VectorMacro
{
    public static function create(): void
    {
        Blueprint::macro('vectorIndex', function ($column, $indexName) {
            /** @var Blueprint $this */
            return DB::statement("CREATE INDEX {$indexName} ON {$this->table}(libsql_vector_idx({$column}))");
        });

        Builder::macro('nearest', function ($index_name, $vector, $limit = 10) {
            /** @var Builder $this */
            return $this->joinSub(
                DB::table(DB::raw("vector_top_k('$index_name', '[" . implode(',', $vector) . "]', $limit)")),
                'v',
                "{$this->from}.rowid",
                '=',
                'v.id'
            );
        });
    }
}
