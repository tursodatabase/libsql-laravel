<?php

declare(strict_types=1);

namespace Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\CastingModels;

use Illuminate\Database\Eloquent\Model;
use Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\Enums\Status;

class EnumCastingModel extends Model
{
    protected $table = 'enum_casting_table';

    protected $guarded = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }
}
