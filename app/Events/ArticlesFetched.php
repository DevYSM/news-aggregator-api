<?php

namespace App\Events;

use App\DTOs\Article\NormalizedArticleDto;
use Illuminate\Foundation\Events\Dispatchable;

class ArticlesFetched
{
    use Dispatchable;

    /**
     * @param  NormalizedArticleDto[]  $articles
     */
    public function __construct(public readonly array $articles) {}
}
