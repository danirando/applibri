<?php

namespace App\Console\Commands;

use App\Jobs\ImportBookFromOpenLibrary;
use App\Models\BestSeller;
use App\Models\Book;
use App\Services\OpenLibraryService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class PopularBooksSync extends Command
{
    protected $signature = 'books:sync-popular';

    protected $description = 'Sync popular books from Open Library';

    public function handle(OpenLibraryService $openLibraryService): int
    {
        $popularBooks = $openLibraryService->getTrendingBooks(period: 'weekly', limit: 6);
        $processed = 0;
        $bestSellersUpdated = 0;
        $weekDate = today()->toDateString();

        foreach ($popularBooks as $index => $popularBook) {
            $book = Book::where('external_id', $popularBook['open_library_key'] ?? null)->first();

            if (! $book && ! empty($popularBook['open_library_key'])) {
                $book = (new ImportBookFromOpenLibrary(
                    openLibraryKey: $popularBook['open_library_key'],
                    title: $popularBook['title'],
                    authorNames: $popularBook['author_names'],
                    coverId: $popularBook['cover_id'],
                ))->importAndReturnBook($openLibraryService);
            }

            if ($book) {
                BestSeller::updateOrCreate(
                    [
                        'book_id' => $book->id,
                        'list_name' => 'popular',
                        'week_date' => $weekDate,
                    ],
                    ['rank' => $index + 1]
                );
                $bestSellersUpdated++;
            }

            $processed++;
        }

        $this->info("Processati {$processed} libri; {$bestSellersUpdated} best-seller aggiunti/aggiornati.");

        return SymfonyCommand::SUCCESS;
    }
}
