<?php

use App\Models\Article;
use App\Models\User;
use App\Models\UserPreference;
use Tymon\JWTAuth\Facades\JWTAuth;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = JWTAuth::fromUser($this->user);
});

it('requires authentication', function () {
    $this->getJson('/api/v1/preferences/feed')
        ->assertStatus(401);
});

it('returns recent articles when no preferences set', function () {
    Article::factory(5)->create();

    $this->getJson('/api/v1/preferences/feed', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

it('returns articles matching preferred source', function () {
    Article::factory(3)->create(['source' => 'newsapi']);
    Article::factory(2)->create(['source' => 'guardian']);

    UserPreference::factory()->create([
        'user_id' => $this->user->id,
        'sources' => ['newsapi'],
        'categories' => [],
        'authors' => [],
    ]);

    $this->getJson('/api/v1/preferences/feed', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('returns articles matching preferred category', function () {
    Article::factory(2)->create(['category' => 'technology']);
    Article::factory(3)->create(['category' => 'sports']);

    UserPreference::factory()->create([
        'user_id' => $this->user->id,
        'sources' => [],
        'categories' => ['technology'],
        'authors' => [],
    ]);

    $this->getJson('/api/v1/preferences/feed', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns articles matching preferred author', function () {
    Article::factory(2)->create(['author' => 'Jane Doe']);
    Article::factory(3)->create(['author' => 'John Smith']);

    UserPreference::factory()->create([
        'user_id' => $this->user->id,
        'sources' => [],
        'categories' => [],
        'authors' => ['Jane Doe'],
    ]);

    $this->getJson('/api/v1/preferences/feed', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('paginates the feed', function () {
    Article::factory(30)->create();

    $this->getJson('/api/v1/preferences/feed?per_page=10', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonCount(10, 'data');
});
