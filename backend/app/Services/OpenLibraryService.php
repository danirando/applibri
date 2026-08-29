<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenLibraryService
{
    protected string $baseUrl = 'https://openlibrary.org';
    protected string $coversBaseUrl = 'https://covers.openlibrary.org';
    protected int $timeout = 5;

    /**
     * Search books on Open Library.
     *
     * @return array<int, array{
     *     title: string,
     *     author_names: array<string>,
     *     first_publish_year: int|null,
     *     cover_id: int|null,
     *     cover_url: string|null,
     *     open_library_key: string|null,
     *     isbn: string|null
     * }>
     */
    public function search(string $query): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/search.json", [
                    'q' => $query,
                    'limit' => 10,
                ]);

            if ($response->failed()) {
                Log::warning('OpenLibrary search request failed', [
                    'query' => $query,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $docs = $response->json('docs', []);

            return array_map(function ($doc) {
                $coverId = isset($doc['cover_i']) ? (int) $doc['cover_i'] : null;
                $isbns = $doc['isbn'] ?? [];
                $firstIsbn = is_array($isbns) && ! empty($isbns) ? (string) $isbns[0] : null;

                return [
                    'title' => $doc['title'] ?? '',
                    'author_names' => $doc['author_name'] ?? [],
                    'first_publish_year' => $doc['first_publish_year'] ?? null,
                    'cover_id' => $coverId,
                    'cover_url' => $this->getCoverUrl($coverId),
                    'open_library_key' => $doc['key'] ?? null,
                    'isbn' => $firstIsbn,
                ];
            }, $docs);
        } catch (Throwable $e) {
            Log::warning('OpenLibrary search exception', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Build cover image URL from cover ID.
     */
    public function getCoverUrl(?int $coverId, string $size = 'M'): ?string
    {
        if ($coverId === null) {
            return null;
        }

        return "{$this->coversBaseUrl}/b/id/{$coverId}-{$size}.jpg";
    }

    /**
     * Get work details from Open Library by key (e.g. /works/OL12345W).
     *
     * @return array{description: string|null, subjects: array<string>}|null
     */
    public function getWorkDetails(string $openLibraryKey): ?array
    {
        try {
            $key = '/'.ltrim($openLibraryKey, '/');
            $url = "{$this->baseUrl}{$key}.json";

            $response = Http::timeout($this->timeout)->get($url);

            if ($response->failed()) {
                Log::warning('OpenLibrary getWorkDetails request failed', [
                    'key' => $openLibraryKey,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();

            // Description can be a string or an object with 'value' key
            $rawDescription = $data['description'] ?? null;
            $description = null;
            if (is_string($rawDescription)) {
                $description = $rawDescription;
            } elseif (is_array($rawDescription) && isset($rawDescription['value'])) {
                $description = (string) $rawDescription['value'];
            }

            $subjects = $data['subjects'] ?? [];

            return [
                'description' => $description,
                'subjects' => is_array($subjects) ? $subjects : [],
            ];
        } catch (Throwable $e) {
            Log::warning('OpenLibrary getWorkDetails exception', [
                'key' => $openLibraryKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get the earliest publication date from editions.
     */
    public function getEarliestPublishDate(string $openLibraryKey): ?string
    {
        try {
            $key = '/'.ltrim($openLibraryKey, '/');
            $url = "{$this->baseUrl}{$key}/editions.json";

            $response = Http::timeout($this->timeout)->get($url, [
                'limit' => 50,
            ]);

            if ($response->failed()) {
                Log::warning('OpenLibrary getEarliestPublishDate request failed', [
                    'key' => $openLibraryKey,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $entries = $response->json('entries', []);
            $earliest = null;

            foreach ($entries as $entry) {
                $rawDate = $entry['publish_date'] ?? null;
                if (! is_string($rawDate) || trim($rawDate) === '') {
                    continue;
                }

                $trimmed = trim($rawDate);

                try {
                    $parsed = Carbon::parse($trimmed)->startOfDay();
                } catch (Throwable) {
                    if (preg_match('/\b(\d{4})\b/', $trimmed, $matches)) {
                        try {
                            $parsed = Carbon::createFromDate((int) $matches[1], 1, 1)->startOfDay();
                        } catch (Throwable) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }

                if ($parsed->year >= 1000 && $parsed->year <= 2100) {
                    if ($earliest === null || $parsed->lt($earliest)) {
                        $earliest = $parsed;
                    }
                }
            }

            return $earliest ? $earliest->format('Y-m-d') : null;
        } catch (Throwable $e) {
            Log::warning('OpenLibrary getEarliestPublishDate exception', [
                'key' => $openLibraryKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
