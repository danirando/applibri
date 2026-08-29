<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RssNewsService
{
    /**
     * List of RSS feeds to consume.
     *
     * @var array<int, array{url: string, source_name: string, language: string}>
     */
    protected array $feeds = [
        [
            'url' => 'https://www.illibraio.it/feed/',
            'source_name' => 'ilLibraio.it',
            'language' => 'it',
        ],
        [
            'url' => 'https://www.theguardian.com/books/rss',
            'source_name' => 'The Guardian Books',
            'language' => 'en',
        ],
    ];

    /**
     * Fetch and parse articles from all configured RSS feeds using native PHP SimpleXML.
     *
     * @return array<int, array{
     *     title: string,
     *     excerpt: string|null,
     *     source_name: string,
     *     source_url: string,
     *     image_url: string|null,
     *     published_at: string,
     *     language: string
     * }>
     */
    public function fetchAll(): array
    {
        $articles = [];

        foreach ($this->feeds as $feedConfig) {
            $url = $feedConfig['url'];
            $sourceName = $feedConfig['source_name'];
            $language = $feedConfig['language'];

            try {
                $response = Http::timeout(10)->get($url);

                if ($response->failed()) {
                    Log::warning('Failed to fetch RSS feed response', [
                        'url' => $url,
                        'source_name' => $sourceName,
                        'status' => $response->status(),
                    ]);

                    continue;
                }

                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($xml === false) {
                    $errors = array_map(fn ($err) => trim($err->message), libxml_get_errors());
                    libxml_clear_errors();

                    Log::warning('Failed to parse RSS feed XML', [
                        'url' => $url,
                        'source_name' => $sourceName,
                        'errors' => $errors,
                    ]);

                    continue;
                }

                // Handle standard RSS 2.0 (<channel><item>) or Atom/RSS root items
                $items = $xml->channel->item ?? $xml->item ?? [];

                foreach ($items as $item) {
                    $title = trim((string) $item->title);
                    $sourceUrl = trim((string) $item->link);

                    if ($title === '' || $sourceUrl === '') {
                        continue;
                    }

                    $rawDescription = (string) $item->description;
                    $cleanDescription = trim(html_entity_decode(strip_tags($rawDescription)));
                    $excerpt = $cleanDescription !== '' ? Str::limit($cleanDescription, 300) : null;

                    // Extract image from enclosure if available
                    $imageUrl = null;
                    if (isset($item->enclosure['url'])) {
                        $imageUrl = (string) $item->enclosure['url'];
                    }

                    // Parse publication date
                    try {
                        $publishedAt = Carbon::parse((string) $item->pubDate)->format('Y-m-d H:i:s');
                    } catch (Throwable) {
                        $publishedAt = Carbon::now()->format('Y-m-d H:i:s');
                    }

                    $articles[] = [
                        'title' => $title,
                        'excerpt' => $excerpt,
                        'source_name' => $sourceName,
                        'source_url' => $sourceUrl,
                        'image_url' => $imageUrl,
                        'published_at' => $publishedAt,
                        'language' => $language,
                    ];
                }
            } catch (Throwable $e) {
                Log::warning('Exception while processing RSS feed', [
                    'url' => $url,
                    'source_name' => $sourceName,
                    'language' => $language,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $articles;
    }
}
