<?php

namespace App\DTOs\Article;

readonly class ArticleFiltersDto
{
    /**
     * @param string|null $keyword
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param string|null $category
     * @param string|null $source
     * @param string|null $author
     * @param int         $perPage
     */
    public function __construct(
        public ?string $keyword = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $category = null,
        public ?string $source = null,
        public ?string $author = null,
        public int $perPage = 15,
    ) {}

    /**
     * @param array $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            keyword: $data['keyword'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            category: $data['category'] ?? null,
            source: $data['source'] ?? null,
            author: $data['author'] ?? null,
            perPage: $data['per_page'] ?? 15,
        );
    }
}
