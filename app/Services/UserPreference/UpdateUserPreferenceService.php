<?php

namespace App\Services\UserPreference;

use App\DTOs\UserPreference\UserPreferenceDto;
use App\Models\User;
use App\Models\UserPreference;

class UpdateUserPreferenceService
{
    /**
     * @param \App\Models\User                           $user
     * @param \App\DTOs\UserPreference\UserPreferenceDto $dto
     *
     * @return \App\Models\UserPreference
     */
    public function handle(User $user, UserPreferenceDto $dto): UserPreference
    {
        $preference = $user->preference ?? new UserPreference(['user_id' => $user->id]);
        $preference->fill($dto->toArray())->save();

        return $preference;
    }
}
