<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'external_id' => fake()->uuid(),
            'source' => fake()->randomElement(['newsapi', 'guardian', 'nyt']),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => fake()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'author' => fake()->name(),
            'category' => fake()->randomElement(['technology', 'sports', 'politics', 'health', 'science', 'business']),
            'url' => fake()->url(),
            'image_url' => fake()->imageUrl(),
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
