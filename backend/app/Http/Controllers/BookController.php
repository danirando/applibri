<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Jobs\ImportBookFromOpenLibrary;
use App\Models\Book;
use App\Services\OpenLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * Search books in local database, and fetch from Open Library if few results are found.
     */
    public function search(Request $request, OpenLibraryService $openLibraryService): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json([
                'message' => 'Il parametro di ricerca "q" è obbligatorio.',
                'errors' => [
                    'q' => ['Il parametro "q" non può essere vuoto.'],
                ],
            ], 422);
        }

        $localBooks = Book::with(['authors', 'genres'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('subtitle', 'like', "%{$query}%");
            })
            ->paginate(15);

        $externalResults = [];

        // If fewer than 5 results exist locally, search Open Library
        if ($localBooks->total() < 5) {
            $openLibraryResults = $openLibraryService->search($query);

            if (! empty($openLibraryResults)) {
                $keys = array_filter(array_column($openLibraryResults, 'open_library_key'));
                $existingKeys = Book::whereIn('external_id', $keys)->pluck('external_id')->toArray();

                foreach ($openLibraryResults as $result) {
                    $key = $result['open_library_key'] ?? null;
                    $isAlreadyInDb = $key && in_array($key, $existingKeys, true);

                    // Dispatch async import job for new books
                    if ($key && ! $isAlreadyInDb) {
                        ImportBookFromOpenLibrary::dispatch(
                            $key,
                            $result['title'],
                            $result['author_names'],
                            $result['cover_id'],
                            $result['isbn']
                        );
                    }

                    $externalResults[] = [
                        'title' => $result['title'],
                        'author_names' => $result['author_names'],
                        'cover_url' => $result['cover_url'],
                        'open_library_key' => $key,
                        'importing' => ! $isAlreadyInDb,
                    ];
                }
            }
        }

        return response()->json([
            'local' => BookResource::collection($localBooks)->response()->getData(true),
            'external' => $externalResults,
        ]);
    }

    /**
     * Display the specified book.
     */
    public function show(int|string $id): BookResource
    {
        $book = Book::with(['authors', 'genres'])->findOrFail($id);

        return new BookResource($book);
    }

    /**
     * Get the latest published books.
     */
    public function latest(): AnonymousResourceCollection
    {
        $books = Book::with('authors')
            ->orderByDesc('published_date')
            ->paginate(20);

        return BookResource::collection($books);
    }
}
