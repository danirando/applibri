<?php

namespace App\Jobs;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Services\OpenLibraryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportBookFromOpenLibrary implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array<int, string>  $authorNames
     */
    public function __construct(
        public string $openLibraryKey,
        public string $title,
        public array $authorNames = [],
        public ?int $coverId = null,
        public ?string $isbn = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OpenLibraryService $openLibraryService): void
    {
        try {
            // Check if book already exists by external_id or isbn
            $exists = Book::where('external_id', $this->openLibraryKey)
                ->when($this->isbn, function ($query, $isbn) {
                    $cleaned = preg_replace('/[^0-9X]/i', '', $isbn);
                    $query->orWhere('isbn_13', $cleaned)
                        ->orWhere('isbn_10', $cleaned);
                })
                ->exists();

            if ($exists) {
                return;
            }

            // Fetch additional details from Open Library
            $details = $openLibraryService->getWorkDetails($this->openLibraryKey);
            $coverUrl = $openLibraryService->getCoverUrl($this->coverId, 'L');
            $publishedDate = $openLibraryService->getEarliestPublishDate($this->openLibraryKey);

            // Format ISBN
            $cleanedIsbn = $this->isbn ? preg_replace('/[^0-9X]/i', '', $this->isbn) : null;
            $isbn13 = ($cleanedIsbn && strlen($cleanedIsbn) === 13) ? $cleanedIsbn : null;
            $isbn10 = ($cleanedIsbn && strlen($cleanedIsbn) === 10) ? $cleanedIsbn : null;

            if ($cleanedIsbn && ! $isbn13 && ! $isbn10) {
                $isbn13 = $cleanedIsbn;
            }

            DB::transaction(function () use ($details, $coverUrl, $publishedDate, $isbn13, $isbn10) {
                $book = Book::create([
                    'title' => $this->title,
                    'description' => $details['description'] ?? null,
                    'cover_url' => $coverUrl,
                    'published_date' => $publishedDate,
                    'isbn_13' => $isbn13,
                    'isbn_10' => $isbn10,
                    'external_id' => $this->openLibraryKey,
                    'source' => 'open_library',
                ]);

                // Authors
                foreach ($this->authorNames as $authorName) {
                    $trimmed = trim($authorName);
                    if ($trimmed === '') {
                        continue;
                    }

                    $author = Author::firstOrCreate(['name' => $trimmed]);
                    $book->authors()->syncWithoutDetaching([$author->id]);
                }

                // Genres / Subjects (filter and take top 5 valid)
                $subjects = $details['subjects'] ?? [];
                $validGenres = [];

                foreach ($subjects as $subject) {
                    if (count($validGenres) >= 5) {
                        break;
                    }

                    if (! is_string($subject)) {
                        continue;
                    }

                    $trimmed = trim($subject);
                    if ($trimmed === '') {
                        continue;
                    }

                    // 1. Exclude subjects containing ":"
                    if (str_contains($trimmed, ':')) {
                        continue;
                    }

                    // 2. Exclude subjects containing "(" or ")"
                    if (str_contains($trimmed, '(') || str_contains($trimmed, ')')) {
                        continue;
                    }

                    // 3. Exclude subjects longer than 25 characters
                    if (mb_strlen($trimmed) > 25) {
                        continue;
                    }

                    // 4. Exclude subjects containing non-ASCII characters
                    if (preg_match('/[^\x20-\x7E]/', $trimmed)) {
                        continue;
                    }

                    // 5. Normalize subject with trim() and ucfirst()
                    $normalized = ucfirst($trimmed);

                    if (! in_array($normalized, $validGenres, true)) {
                        $validGenres[] = $normalized;
                    }
                }

                foreach ($validGenres as $genreName) {
                    $genre = Genre::firstOrCreate(['name' => $genreName]);
                    $book->genres()->syncWithoutDetaching([$genre->id]);
                }
            });
        } catch (Throwable $e) {
            Log::error('ImportBookFromOpenLibrary failed', [
                'open_library_key' => $this->openLibraryKey,
                'title' => $this->title,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
