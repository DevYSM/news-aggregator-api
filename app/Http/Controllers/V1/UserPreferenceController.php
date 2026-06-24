<?php

namespace App\Http\Controllers\V1;

use App\DTOs\UserPreference\UserPreferenceDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UserPreference\UpdateUserPreferenceRequest;
use App\Http\Resources\V1\ArticleResource;
use App\Http\Resources\V1\UserPreferenceResource;
use App\Services\UserPreference\GetUserPreferenceService;
use App\Services\UserPreference\PersonalizedFeedService;
use App\Services\UserPreference\UpdateUserPreferenceService;
use Illuminate\Http\JsonResponse;

class UserPreferenceController extends Controller
{
    /**
     * @param \App\Services\UserPreference\GetUserPreferenceService    $getPreferenceService
     * @param \App\Services\UserPreference\UpdateUserPreferenceService $updatePreferenceService
     * @param \App\Services\UserPreference\PersonalizedFeedService     $personalizedFeedService
     */
    public function __construct(
        private readonly GetUserPreferenceService    $getPreferenceService,
        private readonly UpdateUserPreferenceService $updatePreferenceService,
        private readonly PersonalizedFeedService     $personalizedFeedService,
    )
    {
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(): JsonResponse
    {
        $preference = $this->getPreferenceService->handle(auth()->user());

        return success(
            message: 'Preferences retrieved successfully',
            data: new UserPreferenceResource($preference),
        );
    }

    /**
     * @param \App\Http\Requests\V1\UserPreference\UpdateUserPreferenceRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserPreferenceRequest $request): JsonResponse
    {
        $dto = UserPreferenceDto::fromArray($request->validated());
        $preference = $this->updatePreferenceService->handle(
            user: auth()->user(),
            dto: $dto,
        );

        return success(
            message: 'Preferences updated successfully',
            data: new UserPreferenceResource($preference),
        );
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function feed(): JsonResponse
    {
        $perPage = request()->integer('per_page', 15);
        $user = auth()->user()->loadMissing('preference');
        $paginator = $this->personalizedFeedService->handle($user, $perPage);

        return success(
            message: 'Personalized feed retrieved successfully',
            data: ArticleResource::collection($paginator),
            paginator: $paginator,
        );
    }
}
