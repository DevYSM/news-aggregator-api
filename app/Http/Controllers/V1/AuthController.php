<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Http\Resources\V1\UserResource;
use App\Services\Auth\LoginService;
use App\Services\Auth\RegisterService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * @param \App\Services\Auth\LoginService    $loginService
     * @param \App\Services\Auth\RegisterService $registerService
     */
    public function __construct(
        private readonly LoginService    $loginService,
        private readonly RegisterService $registerService,
    )
    {
    }

    /**
     * @throws \App\Exceptions\AppException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $response = $this->loginService->handle(
            email: $request->validated('email'),
            password: $request->validated('password')
        );

        return success(
            message: 'Login successful',
            data: $response
        );
    }

    /**
     * @param \App\Http\Requests\V1\Auth\RegisterRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $response = $this->registerService->handle($request->validated());

        return success(
            message: 'Registration successful',
            data: $response,
            code: 201
        );
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return success(message: 'Logged out successfully');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        return success(
            message: 'Profile retrieved successfully',
            data: auth()->user()->toResource(UserResource::class)
        );
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return success(
            message: 'Token refreshed successfully',
            data: [
                'access_token' => auth()->refresh(),
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
            ]
        );
    }
}
