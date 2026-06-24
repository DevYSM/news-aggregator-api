<?php

namespace App\Contracts;

use App\DTOs\Article\NormalizedArticleDto;

interface NewsSourceInterface
{
    /**
     * @return NormalizedArticleDto[]
     */
    public function fetch(): array;

    public function sourceName(): string;
}
