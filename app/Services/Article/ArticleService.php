<?php

namespace App\Services\Article;

use App\DTOs\Article\ArticleFiltersDto;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArticleService
{
    /**
     * @param \App\DTOs\Article\ArticleFiltersDto $filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function handle(ArticleFiltersDto $filters): LengthAwarePaginator
    {
        return Article::query()
            ->byKeyword($filters->keyword)
            ->byDateFrom($filters->dateFrom)
            ->byDateTo($filters->dateTo)
            ->byCategory($filters->category)
            ->bySource($filters->source)
            ->byAuthor($filters->author)
            ->orderBy('published_at', 'desc')
            ->paginate($filters->perPage);
    }
}
