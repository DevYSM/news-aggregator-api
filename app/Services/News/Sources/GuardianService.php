<?php

namespace App\Services\News\Sources;

use App\DTOs\Article\NormalizedArticleDto;
use App\Enums\NewsSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class GuardianService extends AbstractNewsSource
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
            ->get('https://content.guardianapis.com/search', [
                'api-key' => config('news.guardian.key'),
                'show-fields' => 'bodyText,thumbnail,byline,trailText',
                'page-size' => config('news.guardian.page_size', 50),
                'order-by' => 'newest',
            ])
            ->throw();

        return collect($response->json('response.results', []))
            ->map(fn (array $item) => $this->normalize($item))
            ->filter(fn (NormalizedArticleDto $dto) => filled($dto->externalId) && filled($dto->title))
            ->values()
            ->all();
    }

    private function normalize(array $item): NormalizedArticleDto
    {
        return new NormalizedArticleDto(
            externalId: $item['id'] ?? '',
            source: $this->sourceName(),
            title: $item['webTitle'] ?? '',
            description: $item['fields']['trailText'] ?? null,
            content: $item['fields']['bodyText'] ?? null,
            author: $item['fields']['byline'] ?? null,
            category: $item['sectionName'] ?? null,
            url: $item['webUrl'] ?? '',
            imageUrl: $item['fields']['thumbnail'] ?? null,
            publishedAt: $item['webPublicationDate'] ?? null,
        );
    }

    public function sourceName(): string
    {
        return NewsSource::Guardian->value;
    }
}
