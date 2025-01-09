<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovieController extends Controller
{
    public function index()
    {
        $movies = Movie::all();
        return view("movies.index", compact("movies"));
    }

    public function store(Request $request)
    {
        $movie = new Movie();
        $movie->title = $request->input("title");
        $movie->genre = $request->input("genre");
        $movie->release_year = $request->input("release_year");
        $movie->plot_embedding = $request->input("plot_embedding");
        $movie->save();

        return redirect()->route("movies.index");
    }

    public function search(Request $request)
    {
        $query = $request->input("query");
        $embedding = json_decode($query);

        $results = DB::table("movies")
            ->select("*")
            ->selectRaw(
                "libsql_vector_cosine_similarity(plot_embedding, vector32(?)) as similarity",
                [json_encode($embedding)]
            )
            ->orderByDesc("similarity")
            ->limit(5)
            ->get();

        return view("movies.search", compact("results", "query"));
    }
}
