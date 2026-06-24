<?php

namespace App\Services\News\Sources;

use App\DTOs\Article\NormalizedArticleDto;
use App\Enums\NewsSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class NytService extends AbstractNewsSource
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
            ->get('https://api.nytimes.com/svc/topstories/v2/home.json', [
                'api-key' => config('news.nyt.key'),
            ])
            ->throw();

        return collect($response->json('results', []))
            ->map(fn (array $item) => $this->normalize($item))
            ->filter(fn (NormalizedArticleDto $dto) => filled($dto->externalId) && filled($dto->title))
            ->values()
            ->all();
    }

    private function normalize(array $item): NormalizedArticleDto
    {
        $image = collect($item['multimedia'] ?? [])
            ->firstWhere('format', 'threeByTwoSmallAt2X');

        return new NormalizedArticleDto(
            externalId: $item['uri'] ?? md5($item['url'] ?? ''),
            source: $this->sourceName(),
            title: $item['title'] ?? '',
            description: $item['abstract'] ?? null,
            content: null,
            author: $item['byline'] ?? null,
            category: $item['section'] ?? null,
            url: $item['url'] ?? '',
            imageUrl: $image['url'] ?? null,
            publishedAt: $item['published_date'] ?? null,
        );
    }

    public function sourceName(): string
    {
        return NewsSource::Nyt->value;
    }
}
