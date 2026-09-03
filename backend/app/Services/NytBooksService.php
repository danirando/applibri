<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class NytBooksService
{
    public function getCurrentBestSellers(string $listName = 'combined-print-and-e-book-fiction', int $limit = 6): array
    {
        try {
            $response = Http::timeout(8)->get(
                "https://api.nytimes.com/svc/books/v3/lists/current/{$listName}.json",
                [
                    'api-key' => config('services.nyt.books_api_key'),
                ]
            );

            if ($response->failed()) {
                Log::warning('NYT current best sellers request failed', [
                    'list_name' => $listName,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $books = $response->json('results.books', []);
            $results = [];

            foreach ($books as $book) {
                if (count($results) >= $limit) {
                    break;
                }

                $isbn13 = $book['primary_isbn13'] ?? null;

                if (! is_string($isbn13)) {
                    continue;
                }

                $isbn13 = preg_replace('/[^0-9X]/i', '', $isbn13);

                if (! is_string($isbn13) || strlen($isbn13) !== 13) {
                    continue;
                }

                $results[] = [
                    'title' => $book['title'] ?? '',
                    'author' => $book['author'] ?? '',
                    'isbn13' => $isbn13,
                ];
            }

            return $results;
        } catch (Throwable $e) {
            Log::warning('NYT current best sellers request exception', [
                'list_name' => $listName,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
