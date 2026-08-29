<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\RssNewsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ArticlesFetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest book news from configured RSS feeds and upsert them into the database';

    /**
     * Execute the console command.
     */
    public function handle(RssNewsService $rssNewsService): int
    {
        $this->info('Inizio recupero articoli dai feed RSS...');

        $articles = $rssNewsService->fetchAll();
        $total = 0;
        $created = 0;
        $updated = 0;

        foreach ($articles as $item) {
            $article = Article::updateOrCreate(
                ['source_url' => $item['source_url']],
                [
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'],
                    'source_name' => $item['source_name'],
                    'image_url' => $item['image_url'],
                    'published_at' => $item['published_at'],
                    'language' => $item['language'],
                ]
            );

            if ($article->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }

            $total++;
        }

        $summary = "Processati {$total} articoli: {$created} nuovi, {$updated} aggiornati.";

        Log::info("Articles fetch completed: {$summary}");
        $this->info($summary);

        return SymfonyCommand::SUCCESS;
    }
}
