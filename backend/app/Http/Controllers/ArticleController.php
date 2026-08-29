<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles ordered by published date.
     */
    public function index(): AnonymousResourceCollection
    {
        $articles = Article::orderByDesc('published_at')
            ->paginate(20);

        return ArticleResource::collection($articles);
    }
}
