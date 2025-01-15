<?php

declare(strict_types=1);

namespace Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\CastingModels;

use Illuminate\Database\Eloquent\Model;

class ArrayCastingModel extends Model
{
    protected $table = 'array_casting_table';

    protected $guarded = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
