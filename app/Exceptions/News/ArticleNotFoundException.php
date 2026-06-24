<?php

namespace App\Exceptions\News;

use Exception;
use Illuminate\Http\JsonResponse;

class ArticleNotFoundException extends Exception
{
    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct(message: 'Article not found', code: 404);
    }

    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request): JsonResponse
    {
        return error(message: $this->getMessage(), code: $this->getCode());
    }
}
