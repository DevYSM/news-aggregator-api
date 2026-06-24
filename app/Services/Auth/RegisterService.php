<?php


namespace App\Services\Auth;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisterService
{
    /**
     * @param array $attributes
     *
     * @return array
     */
    public function handle(array $attributes): array
    {
        $user = User::create($attributes);

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ];
    }
}
