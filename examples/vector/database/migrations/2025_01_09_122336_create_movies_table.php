<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("movies", function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->string("genre");
            $table->integer("release_year");
            $table->vector("plot_embedding", 5); // 5-dimensional vector
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("movies");
    }
};
