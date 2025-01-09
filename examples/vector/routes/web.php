<?php

use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return view("welcome");
});

Route::get("/movies", [MovieController::class, "index"])->name("movies.index");
Route::post("/movies", [MovieController::class, "store"])->name("movies.store");
Route::get("/movies/search", [MovieController::class, "search"])->name(
    "movies.search"
);
