<?php

namespace App\Http\Controllers\V1;

use App\DTOs\Article\ArticleFiltersDto;
use App\Exceptions\News\ArticleNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Article\ListArticlesRequest;
use App\Http\Resources\V1\ArticleResource;
use App\Services\Article\ArticleService;
use App\Services\Article\ShowArticleService;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    /**
     * @param \App\Services\Article\ArticleService     $articleService
     * @param \App\Services\Article\ShowArticleService $showArticleService
     */
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly ShowArticleService $showArticleService,
    ) {}

    /**
     * @param \App\Http\Requests\V1\Article\ListArticlesRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListArticlesRequest $request): JsonResponse
    {
        $filters = ArticleFiltersDto::fromArray($request->validated());
        $paginator = $this->articleService->handle($filters);

        return success(
            message: 'Articles retrieved successfully',
            data: ArticleResource::collection($paginator),
            paginator: $paginator,
        );
    }

    /**
     * @throws ArticleNotFoundException
     */
    public function show(string $slug): JsonResponse
    {
        $article = $this->showArticleService->handle($slug);

        return success(
            message: 'Article retrieved successfully',
            data: new ArticleResource($article),
        );
    }
}
