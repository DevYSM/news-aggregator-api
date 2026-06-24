<?php

use App\Models\User;
use App\Models\UserPreference;
use Tymon\JWTAuth\Facades\JWTAuth;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = JWTAuth::fromUser($this->user);
});

it('requires authentication', function () {
    $this->getJson('/api/v1/preferences')
        ->assertStatus(401);
});

it('returns empty preferences when none set', function () {
    $this->getJson('/api/v1/preferences', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonPath('data.sources', [])
        ->assertJsonPath('data.categories', [])
        ->assertJsonPath('data.authors', []);
});

it('returns existing preferences', function () {
    UserPreference::factory()->create([
        'user_id' => $this->user->id,
        'sources' => ['newsapi', 'guardian'],
        'categories' => ['technology'],
        'authors' => ['John Doe'],
    ]);

    $this->getJson('/api/v1/preferences', ['Authorization' => "Bearer {$this->token}"])
        ->assertOk()
        ->assertJsonPath('data.sources', ['newsapi', 'guardian'])
        ->assertJsonPath('data.categories', ['technology'])
        ->assertJsonPath('data.authors', ['John Doe']);
});
