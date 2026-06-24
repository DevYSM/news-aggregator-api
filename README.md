# News Aggregator API – Design & Implementation Guide

## Purpose of this Document

This document explains the architectural decisions, implementation patterns, and testing strategy for the News Aggregator API. It serves as a human-to-human explanation of how the system was designed, which trade-offs were considered, and how the separation of concerns influenced the final implementation.

---

## Problem Overview

The challenge involves three independent responsibilities:

- **News Ingestion** – Reliably fetching and normalizing articles from multiple external news sources
- **Article Access** – Exposing a filterable, paginated article feed to authenticated users
- **Personalization** – Allowing users to define preferences and receive a tailored article feed

These concerns are deliberately decoupled so each can evolve independently and maintain appropriate levels of reliability and safety.

---

## Core Design Principles

Before implementation, three guiding questions shaped the design:

1. What external contracts must be normalized before entering our system?
2. Which operations can be retried safely without producing duplicate data?
3. Which responsibilities belong at the HTTP layer versus the background layer?

These questions removed ambiguity and prevented over-engineering in the wrong areas.

---

## Project Structure

```
app/
├── Console/
│   └── Commands/
│       └── FetchNewsCommand.php
├── Contracts/
│   └── NewsSourceInterface.php
├── DTOs/
│   ├── Article/
│   │   ├── ArticleFiltersDto.php
│   │   └── NormalizedArticleDto.php
│   └── UserPreference/
│       └── UserPreferenceDto.php
├── Enums/
│   └── NewsSource.php
├── Events/
│   └── ArticlesFetched.php
├── Exceptions/
│   ├── AppException.php
│   └── News/
│       └── ArticleNotFoundException.php
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php
│   │   └── V1/
│   │       ├── ArticleController.php
│   │       ├── AuthController.php
│   │       └── UserPreferenceController.php
│   ├── Requests/
│   │   └── V1/
│   │       ├── Article/
│   │       │   └── ListArticlesRequest.php
│   │       ├── Auth/
│   │       │   ├── LoginRequest.php
│   │       │   └── RegisterRequest.php
│   │       └── UserPreference/
│   │           └── UpdateUserPreferenceRequest.php
│   └── Resources/
│       └── V1/
│           ├── ArticleResource.php
│           ├── UserPreferenceResource.php
│           └── UserResource.php
├── Jobs/
│   └── FetchNewsArticlesJob.php
├── Listeners/
│   └── StoreArticlesListener.php
├── Models/
│   ├── Article.php
│   ├── User.php
│   └── UserPreference.php
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    ├── Article/
    │   ├── ArticleService.php
    │   └── ShowArticleService.php
    ├── Auth/
    │   ├── LoginService.php
    │   └── RegisterService.php
    ├── News/
    │   ├── AggregatorService.php
    │   └── Sources/
    │       ├── AbstractNewsSource.php
    │       ├── GuardianService.php
    │       ├── NewsApiService.php
    │       └── NytService.php
    └── UserPreference/
        ├── GetUserPreferenceService.php
        ├── PersonalizedFeedService.php
        └── UpdateUserPreferenceService.php

database/
├── factories/
│   ├── ArticleFactory.php
│   ├── UserFactory.php
│   └── UserPreferenceFactory.php
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000001_create_cache_table.php
│   ├── 0001_01_01_000002_create_jobs_table.php
│   ├── 2026_06_23_200602_create_articles_table.php
│   ├── 2026_06_23_200602_create_user_preferences_table.php
│   ├── 2026_06_23_205507_add_fulltext_index_to_articles_table.php
│   └── 2026_06_24_121510_drop_external_id_index_from_articles_table.php
└── seeders/
    └── DatabaseSeeder.php

tests/
├── Feature/
│   ├── Article/
│   │   ├── ListArticlesTest.php
│   │   └── ShowArticleTest.php
│   ├── News/
│   │   └── FetchNewsCommandTest.php
│   └── UserPreference/
│       ├── GetUserPreferenceTest.php
│       ├── PersonalizedFeedTest.php
│       └── UpdateUserPreferenceTest.php
└── Unit/
    └── ExampleTest.php
```

---

## Part 1: News Aggregation (Fetching Articles)

### Core Problem

External news APIs:

- Return data in different shapes and field names per provider
- May be temporarily unavailable
- Should not be called more than once concurrently

The system must normalize all incoming data to a single internal format regardless of source.

### Key Decision 1: Normalize at the Source Boundary

**Why this matters:**

Each news provider returns a different JSON structure. Allowing provider-specific shapes to leak into the database or business logic would create fragile coupling.

**Implementation:**

Every source service implements `NewsSourceInterface` and returns `NormalizedArticleDto[]`. All field mapping happens inside the source class. Nothing downstream knows which provider the article came from beyond a string identifier.

### Key Decision 2: Abstract Shared HTTP Behavior

**Why this matters:**

All three source services share the same HTTP client configuration: 15-second timeout, 5-second connection timeout, 3 retries with 500ms backoff. Duplicating this across three classes creates drift risk.

**Implementation:**

`AbstractNewsSource` holds a single `client()` method returning a pre-configured `PendingRequest`. All source services extend it and call `$this->client()->get(...)`.

### Key Decision 3: Decouple Fetching from Storage via Events

**Why this matters:**

Fetching articles and persisting them are two separate concerns. Tying them together inside the aggregator creates a class with two reasons to change and makes testing harder.

**Implementation:**

`AggregatorService` fires `ArticlesFetched` after each source fetch. `StoreArticlesListener` handles persistence. This allows the storage strategy to change without touching the aggregation logic.

### Key Decision 4: Idempotent Upsert on Storage

**Why this matters:**

The job runs hourly. The same article may be returned by a source in consecutive runs. Inserting blindly would create duplicates.

**Implementation:**

`StoreArticlesListener` uses `Article::upsert()` with `['source', 'external_id']` as the conflict key. Duplicate articles update their metadata rather than creating new rows.

### News Aggregation Flow

```
Schedule (hourly)
    └── FetchNewsArticlesJob (queued, unique)
            └── AggregatorService::handle()
                    ├── NewsApiService::fetch() → NormalizedArticleDto[]
                    │       └── ArticlesFetched::dispatch()
                    │               └── StoreArticlesListener → Article::upsert()
                    ├── GuardianService::fetch() → NormalizedArticleDto[]
                    │       └── ArticlesFetched::dispatch()
                    │               └── StoreArticlesListener → Article::upsert()
                    └── NytService::fetch() → NormalizedArticleDto[]
                            └── ArticlesFetched::dispatch()
                                    └── StoreArticlesListener → Article::upsert()
```

---

## Part 2: Article Access (API)

### Core Problem

Authenticated users need to:

- Browse all articles with multiple optional filters
- Retrieve a single article by slug
- Receive consistent, paginated JSON responses

### Key Decision 5: Filters via DTO, Scopes on Model

**Why this matters:**

Passing raw request data into service methods creates invisible coupling between HTTP input and query logic. Validating in the controller and querying in the service with no shared contract is error-prone.

**Implementation:**

`ListArticlesRequest` validates input. `ArticleFiltersDto::fromArray()` maps validated data to a typed object. `ArticleService` applies named Eloquent scopes (`byKeyword`, `byDateFrom`, `bySource`, etc.) using the DTO. Each scope is self-contained and independently testable.

### Key Decision 6: Full-Text Search via MySQL Index

**Why this matters:**

`LIKE '%keyword%'` queries on large article tables are slow and cannot use standard B-tree indexes. Full-text search is the appropriate tool for natural language keyword matching across title, description, and content.

**Implementation:**

A dedicated migration adds a `FULLTEXT` index on `(title, description, content)`. The `byKeyword` scope uses `whereFullText()`.

### Article Access Flow

```
GET /api/v1/articles?keyword=&source=&category=&author=&date_from=&date_to=&per_page=
    └── ListArticlesRequest (validate)
            └── ArticleFiltersDto::fromArray()
                    └── ArticleService::handle()
                            └── Article::query() + scopes + paginate()
                                    └── ArticleResource::collection() → JSON

GET /api/v1/articles/{slug}
    └── ShowArticleService::handle()
            └── Article::where('slug') → ArticleResource → JSON
```

---

## Part 3: User Preferences & Personalized Feed

### Core Problem

Users want a feed tailored to their preferred sources, categories, and authors. Preferences must persist and be updatable independently of article fetching.

### Key Decision 7: One Preference Row Per User

**Why this matters:**

Storing preferences as a separate table with a `UNIQUE` constraint on `user_id` enforces the one-to-one relationship at the database level, not just the application level.

**Implementation:**

`user_preferences` table with `user_id` as both foreign key and unique key. JSON columns for `sources`, `categories`, `authors`. `GetUserPreferenceService` uses `firstOrCreate` so users always get a preference object even before setting one.

### Key Decision 8: OR-based Feed Matching

**Why this matters:**

A user who follows both "technology" and "guardian" should see articles matching either — not both simultaneously. AND logic would produce an empty feed for most users.

**Implementation:**

`PersonalizedFeedService` builds a single `where` closure using `orWhereIn` for each preference dimension that has values set. Falls back to a chronological all-articles feed when no preferences are configured.

### Personalized Feed Flow

```
GET /api/v1/preferences/feed
    └── auth()->user()->loadMissing('preference')
            └── PersonalizedFeedService::handle()
                    ├── No preferences set → Article::orderBy(published_at)->paginate()
                    └── Preferences set → Article::where(orWhereIn source|category|author)->paginate()
                            └── ArticleResource::collection() → JSON
```

---

## Design Patterns Used

### 1. Strategy Pattern

**Location:** `app/Services/News/Sources/`

**Purpose:** Interchangeable article fetching strategies per news provider

Each source service implements `NewsSourceInterface`, defining a `fetch(): NormalizedArticleDto[]` contract. `AggregatorService` iterates over an array of sources without knowing their concrete types. Adding a new source requires only a new class — nothing else changes.

### 2. Template Method Pattern

**Location:** `app/Services/News/Sources/AbstractNewsSource.php`

**Purpose:** Share HTTP client setup while letting subclasses define request details

`AbstractNewsSource` provides `client()` with the shared `PendingRequest` configuration. Each concrete source calls `$this->client()->get(url, params)`. The shared behavior is defined once; the variable behavior is in each subclass.

### 3. DTO Pattern

**Location:** `app/DTOs/`

**Purpose:** Typed, immutable data carriers across layer boundaries

`NormalizedArticleDto` carries article data from source services to the listener. `ArticleFiltersDto` carries validated filter input from controller to service. Both are `readonly` classes, preventing accidental mutation after construction.

### 4. Single-Action Service Pattern

**Location:** `app/Services/`

**Purpose:** One class, one responsibility, one public method

Every service class exposes a single `handle()` method. Controllers and jobs call `handle()` without knowing implementation details. Services are trivially testable in isolation.

### 5. Enum-Driven Validation

**Location:** `app/Enums/NewsSource.php`

**Purpose:** Single source of truth for valid source identifiers

`NewsSource` is a backed string enum with a `values()` helper. `ListArticlesRequest` uses `Rule::in(NewsSource::values())` for source validation. Source services return `NewsSource::X->value` from `sourceName()`. Adding a new source requires one new enum case — validation and naming stay in sync automatically.

---

## Database Schema

### Articles Table

```sql
CREATE TABLE articles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    external_id VARCHAR(255) NOT NULL,
    source VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    content LONGTEXT NULL,
    author VARCHAR(255) NULL,
    category VARCHAR(255) NULL,
    url VARCHAR(255) NOT NULL,
    image_url TEXT NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_article (source, external_id),
    INDEX idx_author (author),
    INDEX idx_category (category),
    INDEX idx_published_at (published_at),
    FULLTEXT INDEX ft_search (title, description, content)
);
```

### User Preferences Table

```sql
CREATE TABLE user_preferences (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL UNIQUE,
    sources JSON NULL,
    categories JSON NULL,
    authors JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Users Table

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## API Endpoints

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/auth/register` | No | Register new user |
| POST | `/api/v1/auth/login` | No | Login and receive JWT |
| GET | `/api/v1/auth/profile` | Yes | Get authenticated user |
| POST | `/api/v1/auth/refresh` | Yes | Refresh JWT token |
| POST | `/api/v1/auth/logout` | Yes | Invalidate token |

### Articles

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/articles` | Yes | List articles with filters |
| GET | `/api/v1/articles/{slug}` | Yes | Get single article |

**Available filters:** `keyword`, `source`, `category`, `author`, `date_from`, `date_to`, `per_page`

### User Preferences

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/preferences` | Yes | Get user preferences |
| PUT | `/api/v1/preferences` | Yes | Update user preferences |
| GET | `/api/v1/preferences/feed` | Yes | Get personalized article feed |

---

## Testing Strategy

### Feature Tests

**Location:** `tests/Feature/`

**Coverage:**

- Article listing with each filter applied independently
- Article listing with date range validation
- Source validation against the `NewsSource` enum
- Authentication requirement enforcement
- Single article retrieval by slug
- Preference retrieval and update
- Personalized feed respects user preference settings
- `news:fetch` command dispatches job correctly

**Example:**

```php
it('filters by source', function () {
    Article::factory(3)->create(['source' => 'newsapi']);
    Article::factory(2)->create(['source' => 'guardian']);

    $this->getJson('/api/v1/articles?source=newsapi', $this->authHeaders())
        ->assertOk()
        ->assertJsonCount(3, 'data');
});
```

### Idempotency Tests

**Purpose:** Ensure repeated article ingestion does not create duplicate records

**Test Cases:**

- Same article fetched in two consecutive runs
- Verify `articles` table count remains unchanged
- Verify metadata updates (title, description) on re-fetch

```php
it('upserts articles without creating duplicates', function () {
    StoreArticlesListener::dispatch(new ArticlesFetched($articles));
    StoreArticlesListener::dispatch(new ArticlesFetched($articles));

    expect(Article::count())->toBe(count($articles));
});
```

---

## Operational Considerations

### Scheduled Fetching

The fetch job runs hourly via Laravel's task scheduler:

```php
Schedule::job(FetchNewsArticlesJob::class)
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('fetch-news-articles');
```

- `withoutOverlapping()` — prevents a second dispatch if the previous run is still active
- `onOneServer()` — prevents duplicate dispatches in multi-server deployments
- `ShouldBeUnique` on the job — prevents duplicate queue entries

### Manual Fetch

```bash
# Run synchronously
php artisan news:fetch

# Dispatch to queue
php artisan news:fetch --queue
```

### Back-pressure Handling

Articles are fetched asynchronously via the queue. If the queue worker is stopped:

- New fetch jobs accumulate in the queue
- No articles are lost
- Processing resumes automatically when workers restart

### Rate Limiting

Auth endpoints are rate-limited to prevent brute-force attacks:

- Login: 5 requests per minute
- Register: 10 requests per minute

### Error Handling

- HTTP exceptions in source services are caught and reported per source
- Failed jobs retry 3 times with exponential backoff: 10s → 30s → 60s
- Job timeout is set to 80 seconds to prevent worker starvation

### Slug Generation

Slugs are generated deterministically from the article title and a hash of `source + external_id`:

```php
Str::slug(mb_substr($dto->title, 0, 200)) . '-' . substr(md5($dto->source . $dto->externalId), 0, 8)
```

- Title is truncated to 200 characters before slugifying to stay within `VARCHAR(255)`
- The hash suffix ensures uniqueness when two articles share the same title

---

## Local Setup

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run migrations
php artisan migrate

# Start queue worker
php artisan queue:work

# Fetch news (first run)
php artisan news:fetch
```

### Required Environment Variables

```env
NEWSAPI_KEY=your_newsapi_key
GUARDIAN_KEY=your_guardian_key
NYT_KEY=your_nyt_key

JWT_SECRET=your_jwt_secret
JWT_TTL=60

DB_CONNECTION=mysql
DB_DATABASE=news_aggregator
```

---

## Summary

This implementation achieves:

- **Reliability:** Articles are fetched and stored idempotently — no duplicates on repeated runs
- **Extensibility:** Adding a new news source requires one new class implementing `NewsSourceInterface` and one new `NewsSource` enum case
- **Separation of Concerns:** Fetching, normalizing, storing, and serving articles are handled by distinct classes with no overlap
- **Type Safety:** DTOs enforce typed boundaries between layers — raw arrays never leak across service boundaries
- **Operational Safety:** Background jobs with retries, uniqueness guards, and overlap prevention ensure reliable hourly ingestion
