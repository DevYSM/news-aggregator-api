<?php

namespace App\Services\UserPreference;

use App\Models\User;
use App\Models\UserPreference;

class GetUserPreferenceService
{
    /**
     * @param \App\Models\User $user
     *
     * @return \App\Models\UserPreference
     */
    public function handle(User $user): UserPreference
    {
        return $user->preference ?? UserPreference::create(['user_id' => $user->id]);
    }
}
