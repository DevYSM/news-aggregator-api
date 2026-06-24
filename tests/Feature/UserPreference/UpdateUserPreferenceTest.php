<?php

use App\Models\User;
use App\Models\UserPreference;
use Tymon\JWTAuth\Facades\JWTAuth;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = JWTAuth::fromUser($this->user);
});

it('requires authentication', function () {
    $this->putJson('/api/v1/preferences', [])
        ->assertStatus(401);
});

it('updates user preferences', function () {
    $this->putJson(
        '/api/v1/preferences',
        [
            'sources' => ['newsapi', 'guardian'],
            'categories' => ['technology', 'sports'],
            'authors' => ['Jane Doe'],
        ],
        ['Authorization' => "Bearer {$this->token}"]
    )
        ->assertOk()
        ->assertJsonPath('data.sources', ['newsapi', 'guardian'])
        ->assertJsonPath('data.categories', ['technology', 'sports']);

    expect(UserPreference::where('user_id', $this->user->id)->exists())->toBeTrue();
});

it('rejects invalid source value', function () {
    $this->putJson(
        '/api/v1/preferences',
        ['sources' => ['invalid_source']],
        ['Authorization' => "Bearer {$this->token}"]
    )
        ->assertStatus(422);
});

it('accepts partial update', function () {
    UserPreference::factory()->create([
        'user_id' => $this->user->id,
        'sources' => ['guardian'],
        'categories' => ['health'],
        'authors' => ['Bob'],
    ]);

    $this->putJson(
        '/api/v1/preferences',
        ['sources' => ['newsapi', 'nyt']],
        ['Authorization' => "Bearer {$this->token}"]
    )
        ->assertOk()
        ->assertJsonPath('data.sources', ['newsapi', 'nyt']);
});
