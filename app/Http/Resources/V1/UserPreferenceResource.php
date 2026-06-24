<?php

namespace App\Http\Resources\V1;

use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserPreference */
class UserPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sources' => $this->sources ?? [],
            'categories' => $this->categories ?? [],
            'authors' => $this->authors ?? [],
        ];
    }
}
