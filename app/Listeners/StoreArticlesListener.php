<?php

namespace App\Listeners;

use App\DTOs\Article\NormalizedArticleDto;
use App\Events\ArticlesFetched;
use App\Models\Article;
use Illuminate\Support\Str;

class StoreArticlesListener
{
    /**
     * @param \App\Events\ArticlesFetched $event
     *
     * @return void
     */
    public function handle(ArticlesFetched $event): void
    {
        if (empty($event->articles)) {
            return;
        }

        $now = now();

        $records = array_map(function (NormalizedArticleDto $dto) use ($now): array {
            return [
                ...$dto->toArray(),
                'slug' => Str::slug(mb_substr($dto->title, 0, 200)) . '-' . substr(md5($dto->source . $dto->externalId), 0, 8),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $event->articles);

        Article::upsert(
            $records,
            ['source', 'external_id'],
            ['title', 'description', 'content', 'author', 'category', 'url', 'image_url', 'published_at', 'updated_at'],
        );
    }
}
