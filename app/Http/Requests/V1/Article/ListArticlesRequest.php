<?php

namespace App\Http\Requests\V1\Article;

use App\Enums\NewsSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['sometimes', 'string', 'max:255'],
            'date_from' => ['sometimes', 'date', 'before_or_equal:date_to'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'category' => ['sometimes', 'string', 'max:100'],
            'source' => ['sometimes', 'string', Rule::in(NewsSource::values())],
            'author' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
