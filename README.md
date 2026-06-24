# News Aggregator API

A Laravel 13 REST API that aggregates articles from multiple news sources, exposes a filterable feed, and supports user-personalized content delivery.

---

## Stack

| Layer | Technology |
|-------|-----------|
| Runtime | PHP 8.4, Laravel 13 |
| Auth | JWT (`tymon/jwt-auth`) |
| Queue | Redis + Laravel Horizon |
| Database | MySQL 8 |
| Cache / Session | Redis |
| Containerization | Docker + Nginx |

---

## Quick Start (Docker)

```bash
# 1. Clone and copy environment file
cp .env.example .env

# 2. Fill in required keys (see Environment Variables section)

# 3. Build and start all services
make build
make up

# 4. Generate keys and run migrations
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret
make migrate

# 5. Fetch news articles (first run)
docker compose exec app php artisan news:fetch --queue
```

App runs at `http://localhost:${APP_PORT}` (default `8001`).
Horizon dashboard at `http://localhost:${APP_PORT}/horizon`.

---

## Makefile Commands

| Command | Description |
|---------|-------------|
| `make build` | Build Docker images |
| `make up` | Start all services in background |
| `make down` | Stop all services |
| `make shell` | Open shell inside app container |
| `make migrate` | Run database migrations |
| `make fresh` | Fresh migrate with seeders |
| `make test` | Run test suite |
| `make logs` | Tail all service logs |
| `make horizon-logs` | Tail Horizon worker logs |

---

## Docker Services

| Service | Image | Purpose |
|---------|-------|---------|
| `app` | Custom (PHP 8.4-FPM) | Laravel application |
| `nginx` | `nginx:alpine` | HTTP server |
| `mysql` | `mysql:8` | Primary database |
| `redis` | `redis:7-alpine` | Queue, cache, sessions |
| `horizon` | Same as `app` | Queue worker + monitoring |

---

## Environment Variables

```env
APP_PORT=8001                  # Host port mapped to nginx

DB_HOST=mysql
DB_DATABASE=news_aggregator_api
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=redis

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

JWT_SECRET=                    # php artisan jwt:secret

NEWSAPI_KEY=
GUARDIAN_KEY=
NYT_KEY=

HORIZON_ALLOWED_EMAIL=         # Comma-separated emails for Horizon access in production
```

---

## Project Structure

```
app/
├── Console/Commands/
│   └── FetchNewsCommand.php
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
│   └── News/ArticleNotFoundException.php
├── Http/
│   ├── Controllers/V1/
│   │   ├── ArticleController.php
│   │   ├── AuthController.php
│   │   └── UserPreferenceController.php
│   ├── Requests/V1/
│   │   ├── Article/ListArticlesRequest.php
│   │   ├── Auth/{LoginRequest,RegisterRequest}.php
│   │   └── UserPreference/UpdateUserPreferenceRequest.php
│   └── Resources/V1/
│       ├── ArticleResource.php
│       ├── UserPreferenceResource.php
│       └── UserResource.php
├── Jobs/
│   └── FetchNewsArticlesJob.php
├── Listeners/
│   └── StoreArticlesListener.php
├── Models/
│   ├── Article.php
│   ├── User.php
│   └── UserPreference.php
├── Providers/
│   ├── AppServiceProvider.php
│   └── HorizonServiceProvider.php
└── Services/
    ├── Article/{ArticleService,ShowArticleService}.php
    ├── Auth/{LoginService,RegisterService}.php
    ├── News/
    │   ├── AggregatorService.php
    │   └── Sources/{Abstract,Guardian,NewsApi,Nyt}Service.php
    └── UserPreference/
        ├── GetUserPreferenceService.php
        ├── PersonalizedFeedService.php
        └── UpdateUserPreferenceService.php

config/
├── horizon.php
├── news.php
└── services.php

docker/
├── nginx/default.conf
└── php/local.ini

Dockerfile
docker-compose.yml
Makefile
```

---

## API Endpoints

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/auth/register` | No | Register new user |
| POST | `/api/v1/auth/login` | No | Login, returns JWT |
| GET | `/api/v1/auth/profile` | Yes | Authenticated user profile |
| POST | `/api/v1/auth/refresh` | Yes | Refresh JWT token |
| POST | `/api/v1/auth/logout` | Yes | Invalidate token |

### Articles

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/articles` | Yes | Paginated article list with filters |
| GET | `/api/v1/articles/{slug}` | Yes | Single article by slug |

**Filters:** `keyword`, `source`, `category`, `author`, `date_from`, `date_to`, `per_page`

### Preferences

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/preferences` | Yes | Get user preferences |
| PUT | `/api/v1/preferences` | Yes | Update user preferences |
| GET | `/api/v1/preferences/feed` | Yes | Personalized article feed |

---

## Architecture

### News Aggregation Flow

```
Scheduler (hourly)
  └── FetchNewsArticlesJob (queued, ShouldBeUnique)
        └── AggregatorService::handle()
              ├── NewsApiService::fetch()  → NormalizedArticleDto[]
              ├── GuardianService::fetch() → NormalizedArticleDto[]
              └── NytService::fetch()      → NormalizedArticleDto[]
                    └── ArticlesFetched::dispatch()
                          └── StoreArticlesListener → Article::upsert()
```

### Key Design Decisions

**Normalize at the source boundary** — Each source service implements `NewsSourceInterface` and returns `NormalizedArticleDto[]`. Provider-specific field mapping stays inside the source class; nothing downstream knows the raw API shape.

**Decouple fetch from storage via events** — `AggregatorService` fires `ArticlesFetched`. `StoreArticlesListener` handles persistence. Storage strategy can change without touching aggregation logic.

**Idempotent upsert** — `Article::upsert()` uses `['source', 'external_id']` as conflict key. Repeated hourly runs update metadata rather than create duplicates.

**DTO boundaries** — `NormalizedArticleDto` and `ArticleFiltersDto` are `readonly` classes. Raw arrays never cross service boundaries.

**Filters via scopes** — `Article` model exposes named `#[Scope]` methods (`byKeyword`, `bySource`, `byCategory`, `byAuthor`, `byDateFrom`, `byDateTo`). `ArticleService` chains them from `ArticleFiltersDto`. Each scope is independently testable and nullable-safe.

**Full-text search** — MySQL `FULLTEXT` index on `(title, description, content)`. `byKeyword` scope uses `whereFullText()`.

**OR-based personalized feed** — `PersonalizedFeedService` builds a single `where` closure using `orWhereIn` per preference dimension. Falls back to chronological all-articles feed when no preferences are set.

---

## Queue & Horizon

Jobs are processed via Redis. Horizon supervises workers and provides a real-time dashboard.

```bash
# Dashboard (local — no auth required)
http://localhost:${APP_PORT}/horizon

# Check status inside container
docker compose exec app php artisan horizon:status

# Pause / resume workers
docker compose exec app php artisan horizon:pause
docker compose exec app php artisan horizon:continue
```

**Production access** — set `HORIZON_ALLOWED_EMAIL` in `.env` (comma-separated for multiple emails). Unauthenticated access is blocked in non-local environments.

The scheduler runs `horizon:snapshot` every 5 minutes to power the metrics graphs in the dashboard.

---

## Manual News Fetch

```bash
# Synchronous (blocks until complete)
docker compose exec app php artisan news:fetch

# Async via queue
docker compose exec app php artisan news:fetch --queue
```

---

## Testing

```bash
make test
```

Feature test coverage:
- Article listing with each filter applied independently
- Date range validation
- Source validation against `NewsSource` enum
- Auth enforcement on all protected routes
- Single article retrieval by slug
- Preference retrieval, update, personalized feed
- `news:fetch` command dispatches job correctly

---

## Rate Limiting

| Endpoint | Limit |
|----------|-------|
| POST `/auth/login` | 5 req / min |
| POST `/auth/register` | 10 req / min |

---

## Error Handling

- Source HTTP failures are caught and reported per source — one failing source does not abort others
- Failed jobs retry 3 times with backoff: 10s → 30s → 60s
- Job timeout: 80 seconds
- `ShouldBeUnique` prevents duplicate fetch jobs in the queue
