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

            if (Book::where('isbn_13', $isbn13)->exists()) {
                continue;
            }

            $matchingResult = $openLibraryService->searchByIsbn($isbn13);
            $openLibraryKey = $matchingResult['open_library_key'] ?? null;

            if (! $openLibraryKey) {
                Log::info('Book not found on Open Library by ISBN', [
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

}
