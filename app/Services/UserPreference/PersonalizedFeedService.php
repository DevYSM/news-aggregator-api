<?php

namespace App\Services\UserPreference;

use App\Models\Article;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PersonalizedFeedService
{
    /**
     * @param \App\Models\User $user
     * @param int              $perPage
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function handle(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $preference = $user->preference;

        $hasSources = filled($preference?->sources);
        $hasCategories = filled($preference?->categories);
        $hasAuthors = filled($preference?->authors);

        if (! $hasSources && ! $hasCategories && ! $hasAuthors) {
            return $this->defaultFeed($perPage);
        }

        return Article::query()
            ->where(function ($query) use ($preference, $hasSources, $hasCategories, $hasAuthors) {
                if ($hasSources) {
                    $query->orWhereIn('source', $preference->sources);
                }

                if ($hasCategories) {
                    $query->orWhereIn('category', $preference->categories);
                }

                if ($hasAuthors) {
                    $query->orWhereIn('author', $preference->authors);
                }
            })
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    private function defaultFeed(int $perPage): LengthAwarePaginator
    {
        return Article::query()
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }
}
