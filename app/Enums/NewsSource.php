<?php

namespace App\Enums;

enum NewsSource: string
{
    case Newsapi = 'newsapi';
    case Guardian = 'guardian';
    case Nyt = 'nyt';

    /**
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
