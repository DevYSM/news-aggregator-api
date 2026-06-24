<?php

namespace App\DTOs\UserPreference;

readonly class UserPreferenceDto
{
    /**
     * @param array|null $sources
     * @param array|null $categories
     * @param array|null $authors
     */
    public function __construct(
        public ?array $sources = null,
        public ?array $categories = null,
        public ?array $authors = null,
    )
    {
    }

    /**
     * @param array $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sources: $data['sources'] ?? null,
            categories: $data['categories'] ?? null,
            authors: $data['authors'] ?? null,
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'sources' => $this->sources,
                'categories' => $this->categories,
                'authors' => $this->authors,
            ],
            fn($value) => $value !== null
        );
    }
}
