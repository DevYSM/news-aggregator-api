<?php

namespace App\Services\News\Sources;

use App\DTOs\Article\NormalizedArticleDto;
use App\Enums\NewsSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class NewsApiService extends AbstractNewsSource
{
    /**
     * @return NormalizedArticleDto[]
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function fetch(): array
    {
        $response = $this->client()
            ->get('https://newsapi.org/v2/top-headlines', [
                'apiKey' => config('news.newsapi.key'),
                'country' => config('news.newsapi.country', 'us'),
                'pageSize' => config('news.newsapi.page_size', 100),
            ])
            ->throw();

        return collect($response->json('articles', []))
            ->map(fn (array $item) => $this->normalize($item))
            ->filter(fn (NormalizedArticleDto $dto) => filled($dto->externalId) && filled($dto->title))
            ->values()
            ->all();
    }

    private function normalize(array $item): NormalizedArticleDto
    {
        return new NormalizedArticleDto(
            externalId: md5($item['url'] ?? ''),
            source: $this->sourceName(),
            title: $item['title'] ?? '',
            description: $item['description'] ?? null,
            content: $item['content'] ?? null,
            author: $item['author'] ?? null,
            category: $item['source']['name'] ?? null,
            url: $item['url'] ?? '',
            imageUrl: $item['urlToImage'] ?? null,
            publishedAt: $item['publishedAt'] ?? null,
        );
    }

    public function sourceName(): string
    {
        return NewsSource::Newsapi->value;
    }
}
