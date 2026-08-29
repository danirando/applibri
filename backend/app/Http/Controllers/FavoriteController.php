<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FavoriteController extends Controller
{
    /**
     * Display the authenticated user's favorite books.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $favorites = $request->user()
            ->favorites()
            ->with(['authors', 'genres'])
            ->get();

        return BookResource::collection($favorites);
    }

    /**
     * Add a book to the user's favorites idempotently.
     */
    public function store(Request $request, int|string $bookId): JsonResponse
    {
        $book = Book::findOrFail($bookId);

        $request->user()->favorites()->syncWithoutDetaching([$book->id]);

        return response()->json([
            'message' => 'Libro aggiunto ai preferiti con successo.',
        ], 201);
    }

    /**
     * Remove a book from the user's favorites.
     */
    public function destroy(Request $request, int|string $bookId): Response
    {
        $request->user()->favorites()->detach($bookId);

        return response()->noContent();
    }
}
