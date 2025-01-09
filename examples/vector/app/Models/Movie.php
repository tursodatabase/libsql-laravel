<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Libsql\Laravel\VectorCast;

class Movie extends Model
{
    protected $fillable = ["title", "genre", "release_year", "plot_embedding"];

    protected $casts = [
        "release_year" => "integer",
        "plot_embedding" => VectorCast::class,
    ];
}
