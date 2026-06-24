<?php

namespace Database\Factories;

use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sources' => fake()->randomElements(['newsapi', 'guardian', 'nyt'], 2),
            'categories' => fake()->randomElements(['technology', 'sports', 'politics', 'health'], 2),
            'authors' => [fake()->name()],
        ];
    }
}
