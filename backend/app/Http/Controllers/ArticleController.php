<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles ordered by published date.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Article::orderByDesc('published_at');

        if ($request->has('lang') && in_array($request->query('lang'), ['it', 'en'], true)) {
            $query->where('language', $request->query('lang'));
        }

        $articles = $query->paginate(20);

        return ArticleResource::collection($articles);
    }
}
