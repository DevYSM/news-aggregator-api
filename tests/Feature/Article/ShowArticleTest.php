<?php

use App\Models\Article;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = JWTAuth::fromUser($this->user);
});

it('requires authentication', function () {
    $article = Article::factory()->create();

    $this->getJson("/api/v1/articles/{$article->slug}")
        ->assertStatus(401);
});

it('returns a single article by slug', function () {
    $article = Article::factory()->create();

    $this->getJson("/api/v1/articles/{$article->slug}", ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonPath('data.slug', $article->slug)
        ->assertJsonPath('data.title', $article->title);
});

it('returns 404 for unknown slug', function () {
    $this->getJson('/api/v1/articles/non-existent-slug', ['Authorization' => "Bearer {$this->token}"])
        ->assertStatus(404);
});
