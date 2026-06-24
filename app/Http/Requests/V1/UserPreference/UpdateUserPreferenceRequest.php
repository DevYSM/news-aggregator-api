<?php

namespace App\Http\Requests\V1\UserPreference;

use Illuminate\Foundation\Http\FormRequest;

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
            'sources.*' => ['string', 'in:newsapi,guardian,nyt'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['string', 'max:100'],
            'authors' => ['sometimes', 'array'],
            'authors.*' => ['string', 'max:255'],
        ];
    }
}
