<?php

namespace App\Services\Auth;

use App\Exceptions\AppException;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginService
{
    /**
     * @param string $email
     * @param string $password
     *
     * @return array
     * @throws \App\Exceptions\AppException
     */
    public function handle(string $email, string $password): array
    {
        if (!$token = JWTAuth::attempt(['email' => $email, 'password' => $password])) {
            throw new AppException(
                message: 'Invalid credentials',
                code: 401
            );
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ];
    }
}
