<?php

namespace App\Http\Requests\V1\UserPreference;

use App\Enums\NewsSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sources' => ['sometimes', 'array'],
            'sources.*' => ['string', new Enum(NewsSource::class)],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['string', 'max:100'],
            'authors' => ['sometimes', 'array'],
            'authors.*' => ['string', 'max:255'],
        ];
    }
}
