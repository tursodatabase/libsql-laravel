<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $movies = [
            [
                "title" => "The Shawshank Redemption",
                "genre" => "Drama",
                "release_year" => 1994,
                "plot_embedding" => [0.1, 0.2, 0.3, 0.4, 0.5],
            ],
            [
                "title" => "The Godfather",
                "genre" => "Crime",
                "release_year" => 1972,
                "plot_embedding" => [0.2, 0.3, 0.4, 0.5, 0.6],
            ],
            [
                "title" => "The Dark Knight",
                "genre" => "Action",
                "release_year" => 2008,
                "plot_embedding" => [0.3, 0.4, 0.5, 0.6, 0.7],
            ],
        ];

        foreach ($movies as $movie) {
            Movie::create($movie);
        }
    }
}
