<?php

namespace App\Console\Commands;

use App\Jobs\ImportBookFromOpenLibrary;
use App\Models\BestSeller;
use App\Models\Book;
use App\Services\NytBooksService;
use App\Services\OpenLibraryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class NewestBooksSync extends Command
{
    protected $signature = 'books:sync-newest';

    protected $description = 'Sync newest books from New York Times reviews';

    public function handle(
        NytBooksService $nytBooksService,
        OpenLibraryService $openLibraryService
    ): int {
        $reviews = $nytBooksService->getCurrentBestSellers(
            listName: 'combined-print-and-e-book-fiction',
            limit: 6
        );
        $processed = 0;
        $imported = 0;

        foreach ($reviews as $review) {
            $processed++;
            $isbn13 = $review['isbn13'];

            $book = Book::where('isbn_13', $isbn13)->first();

            if (! $book) {
                $matchingResult = $openLibraryService->searchByIsbn($isbn13);

                if ($matchingResult === null) {
                    $fallbackResults = $openLibraryService->search(
                        $review['title'].' '.$review['author']
                    );
                    $normalizedTitle = $this->normalizeTitle($review['title']);
                    $discardedTitles = [];

                    foreach ($fallbackResults as $fallbackResult) {
                        $fallbackTitle = $fallbackResult['title'] ?? '';
                        $discardedTitles[] = $fallbackTitle;

                        if ($this->normalizeTitle($fallbackTitle) === $normalizedTitle) {
                            $matchingResult = $fallbackResult;
                            break;
                        }
                    }

                    if ($matchingResult === null) {
                        Log::info('Book not found on Open Library (no exact title match)', [
                            'title' => $review['title'],
                            'author' => $review['author'],
                            'discarded_titles' => $discardedTitles,
                        ]);

                        continue;
                    }
                }

                $openLibraryKey = $matchingResult['open_library_key'] ?? null;

                if (! $openLibraryKey) {
                    Log::info('Book not found on Open Library', [
                        'title' => $review['title'],
                        'author' => $review['author'],
                        'isbn13' => $isbn13,
                    ]);

                    continue;
                }

                $book = (new ImportBookFromOpenLibrary(
                    openLibraryKey: $openLibraryKey,
                    title: $matchingResult['title'] ?: $review['title'],
                    authorNames: $matchingResult['author_names'] ?: [$review['author']],
                    coverId: $matchingResult['cover_id'],
                    isbn: $isbn13,
                ))->importAndReturnBook($openLibraryService);
            }

            if ($book) {
                $imported++;

                BestSeller::updateOrCreate(
                    [
                        'book_id' => $book->id,
                        'list_name' => 'nyt',
                        'week_date' => today()->toDateString(),
                    ],
                    ['rank' => $processed]
                );
            }
        }

        $this->info("Processati {$processed} libri; {$imported} importati con successo.");

        return SymfonyCommand::SUCCESS;
    }

    private function normalizeTitle(string $title): string
    {
        $normalized = mb_strtolower($title);
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized);

        return trim($normalized ?? '');
    }

}
