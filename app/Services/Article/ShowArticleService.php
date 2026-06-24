<?php

namespace App\Services\Article;

use App\Exceptions\News\ArticleNotFoundException;
use App\Models\Article;

class ShowArticleService
{
    /** @throws ArticleNotFoundException */
    public function handle(string $slug): Article
    {
        return Article::where('slug', $slug)->firstOr(
            fn () => throw new ArticleNotFoundException
        );
    }
}
