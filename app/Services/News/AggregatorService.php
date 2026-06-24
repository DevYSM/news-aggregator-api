<?php

namespace App\Services\News;

use App\Contracts\NewsSourceInterface;
use App\Events\ArticlesFetched;

class AggregatorService
{
    /**
     * @param  NewsSourceInterface[]  $sources
     */
    public function __construct(private array $sources) {}

    /**
     * @return void
     */
    public function handle(): void
    {
        foreach ($this->sources as $source) {
            $this->processSource($source);
        }
    }

    /**
     * @param \App\Contracts\NewsSourceInterface $source
     *
     * @return void
     */
    private function processSource(NewsSourceInterface $source): void
    {
        $articles = $source->fetch();

        if (empty($articles)) {
            return;
        }

        ArticlesFetched::dispatch($articles);
    }
}
