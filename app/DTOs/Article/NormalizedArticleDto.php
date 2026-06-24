<?php

namespace App\DTOs\Article;

use Carbon\Carbon;

readonly class NormalizedArticleDto
{
    /**
     * @param string      $externalId
     * @param string      $source
     * @param string      $title
     * @param string|null $description
     * @param string|null $content
     * @param string|null $author
     * @param string|null $category
     * @param string      $url
     * @param string|null $imageUrl
     * @param string|null $publishedAt
     */
    public function __construct(
        public string $externalId,
        public string $source,
        public string $title,
        public ?string $description,
        public ?string $content,
        public ?string $author,
        public ?string $category,
        public string $url,
        public ?string $imageUrl,
        public ?string $publishedAt,
    ) {}

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'source' => $this->source,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'author' => $this->author,
            'category' => $this->category,
            'url' => $this->url,
            'image_url' => $this->imageUrl,
            'published_at' => $this->publishedAt ? Carbon::parse($this->publishedAt)->format('Y-m-d H:i:s') : null,
        ];
    }
}
