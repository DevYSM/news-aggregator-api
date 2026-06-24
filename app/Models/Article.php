<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'external_id', 'source', 'title', 'slug', 'description',
    'content', 'author', 'category', 'url', 'image_url', 'published_at',
])]
class Article extends Model
{
    use HasFactory;

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null                           $keyword
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Scope]
    public function byKeyword(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        return $query->whereFullText(['title', 'description', 'content'], $keyword);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null                           $dateFrom
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Scope]
    public function byDateFrom(Builder $query, ?string $dateFrom): Builder
    {
        if (blank($dateFrom)) {
            return $query;
        }

        return $query->whereDate('published_at', '>=', $dateFrom);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null                           $dateTo
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Scope]
    public function byDateTo(Builder $query, ?string $dateTo): Builder
    {
        if (blank($dateTo)) {
            return $query;
        }

        return $query->whereDate('published_at', '<=', $dateTo);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null                           $category
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Scope]
    public function byCategory(Builder $query, ?string $category): Builder
    {
        if (blank($category)) {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null                           $source
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Scope]
    public function bySource(Builder $query, ?string $source): Builder
    {
        if (blank($source)) {
            return $query;
        }

        return $query->where('source', $source);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null                           $author
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Scope]
    public function byAuthor(Builder $query, ?string $author): Builder
    {
        if (blank($author)) {
            return $query;
        }

        return $query->where('author', 'like', "%{$author}%");
    }

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
