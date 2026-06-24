<?php

use App\Models\Article;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = JWTAuth::fromUser($this->user);
});

it('requires authentication', function () {
    $this->getJson('/api/v1/articles')
        ->assertStatus(401);
});

it('returns paginated articles', function () {
    Article::factory(20)->create();

    $this->getJson('/api/v1/articles', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

it('accepts keyword filter without error', function () {
    Article::factory(3)->create();

    $this->getJson('/api/v1/articles?keyword=Laravel', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

it('filters by source', function () {
    Article::factory(3)->create(['source' => 'newsapi']);
    Article::factory(2)->create(['source' => 'guardian']);

    $this->getJson('/api/v1/articles?source=newsapi', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters by category', function () {
    Article::factory(2)->create(['category' => 'technology']);
    Article::factory(3)->create(['category' => 'sports']);

    $this->getJson('/api/v1/articles?category=technology', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters by author', function () {
    Article::factory(2)->create(['author' => 'Jane Doe']);
    Article::factory(3)->create(['author' => 'John Smith']);

    $this->getJson('/api/v1/articles?author=Jane', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters by date range', function () {
    Article::factory(2)->create(['published_at' => now()->subDays(5)]);
    Article::factory(3)->create(['published_at' => now()->subDays(20)]);

    $this->getJson(
        '/api/v1/articles?date_from='.now()->subDays(7)->toDateString().'&date_to='.now()->toDateString(),
        ['Authorization' => "Bearer {$this->token}"]
    )
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('rejects invalid source value', function () {
    $this->getJson('/api/v1/articles?source=invalid_source', ['Authorization' => "Bearer {$this->token}"])
        ->assertStatus(422);
});

it('rejects date_from after date_to', function () {
    $this->getJson(
        '/api/v1/articles?date_from='.now()->toDateString().'&date_to='.now()->subDay()->toDateString(),
        ['Authorization' => "Bearer {$this->token}"]
    )
        ->assertStatus(422);
});
