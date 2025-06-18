<?php

declare(strict_types=1);

namespace Libsql\Laravel;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\DB;

class VectorCast implements CastsAttributes
{
    public function set($model, $key, $value, $attributes)
    {
        return DB::raw("vector32('[" . implode(',', $value) . "]')");
    }

    public function get($model, $key, $value, $attributes): mixed
    {
        return json_encode($value);
    }
}
