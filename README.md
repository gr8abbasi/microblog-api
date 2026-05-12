# Micro-Blog GraphQL API

A microblogging backend built with Laravel 11 and Lighthouse GraphQL.

## Stack

| | Choice | Why |
|---|---|---|
| Framework | Laravel 11 | Required |
| GraphQL | Lighthouse PHP v6 | Schema-first, handles N+1, pagination and auth out of the box |
| Auth | Laravel Sanctum | Simple token auth, no OAuth overhead |
| Database | SQLite | Zero config for reviewers — swap to MySQL with one `.env` change |
| Testing | PHPUnit 12 | Native, no extras |

---

## Getting Started

**Docker (recommended):**
```bash
git clone https://github.com/gr8abbasi/microblog.git
cd microblog
docker compose up --build -d
```

**Local:**
```bash
git clone https://github.com/gr8abbasi/microblog.git
cd microblog
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

API: `http://localhost:8000/graphql`

---

## Running Tests

```bash
# Docker
docker compose exec app php artisan test

# Local
php artisan test
```

---

## Architecture

```
app/
├── Actions/Post/CreatePostAction.php       # Application layer — use case orchestrator
├── Domain/Post/Rules/PostBodyRule.php      # Domain layer — business rule
├── GraphQL/
│   ├── Builders/FeedBuilder.php            # Global feed with eager loading
│   ├── Builders/UserPostsBuilder.php       # User posts scoped query
│   ├── Exceptions/GraphQLExceptionHandler  # Handles GraphQL error responses
│   └── Mutations/CreatePost.php            # Thin resolver
├── Models/{User,Post}.php
├── Repositories/
│   ├── Contracts/PostRepositoryInterface.php
│   └── PostRepository.php
└── Providers/DomainServiceProvider.php
```

The request flows through clear layers:

```
GraphQL Resolver  →  handles transport (GraphQL input/output)
       ↓
Action            →  orchestrates the use case (application layer)
       ↓
Domain Rule       →  enforces business invariants (domain layer)
       ↓
Repository        →  handles persistence (data layer)
       ↓
Eloquent Model    →  data structure
```

Each layer has one job. Resolvers contain no business logic. Actions contain no GraphQL knowledge. Domain rules contain no framework code.

### Key Decisions

**Repository scope**
`PostRepository` only exposes `create()`. Paginated reads live in Builders because Lighthouse's `@paginate` needs an Eloquent `Builder` — a pre-paginated result from a repository would conflict. The interface boundary is there for when this changes.

**No API Resources**
Lighthouse schema types serve the same purpose — they define what the API exposes. Adding Resources on top would be duplication.

**Form Requests**
Used for the REST login endpoint via `LoginRequest`. For GraphQL mutations, `@rules` in the schema is the equivalent — validation lives next to the contract.

**Validation**
Two layers by design:
- `@rules` in the schema handles type constraints (`required`, `max:280`)
- `PostBodyRule` catches whitespace only posts (a business rule, not a schema rule)

**Lighthouse v6.66 + Laravel 11 bug**
Lighthouse's error pipeline crashes on `ClientAware` exceptions in Laravel 11. `GraphQLExceptionHandler` intercepts these exceptions before they reach the broken pipeline and returns a proper GraphQL error response. The `@rules` directive itself works correctly only the error response path needed fixing.

---

## API

### Get a token

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "kashif@example.com", "password": "password"}'
```

Use the returned token as `Authorization: Bearer <token>` on authenticated requests.

---

### Global Feed

```bash
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{ feed(first: 10) { data { id body createdAt user { username } } paginatorInfo { total hasMorePages } } }"}'
```

---

### User Profile

```bash
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{ user(username: \"kashif\") { id name username posts(first: 5) { data { id body } paginatorInfo { total } } } }"}'
```

---

### Create Post

```bash
curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"query": "mutation { createPost(input: { body: \"Hello world!\" }) { id body user { username } } }"}'
```

**Validation error:**
```json
{
  "errors": [{
    "message": "Validation failed for the field [createPost].",
    "extensions": {
      "validation": { "input.body": ["The input.body field is required."] }
    }
  }]
}
```

**Unauthenticated:**
```json
{
  "errors": [{ "message": "Unauthenticated.", "extensions": { "guards": ["sanctum"] } }]
}
```

---

## Test Credentials

| | |
|---|---|
| Email | kashif@example.com |
| Password | password |
| Username | kashif |

Seeder creates 10 users with 55 posts total.

---

## Performance at Scale

*If the posts table grew to 1,000,000+ rows:*

**Indexes — already applied**
```sql
-- Covers ORDER BY created_at DESC for the global feed
CREATE INDEX posts_feed_index ON posts (created_at, id);

-- Covers WHERE user_id = ? ORDER BY created_at DESC for user profiles
CREATE INDEX posts_user_feed_index ON posts (user_id, created_at);
```

**Cursor pagination**
Offset pagination scans and discards rows — catastrophic at depth. Cursor pagination (`WHERE id < :last_seen_id`) is instant regardless of page. Lighthouse supports it natively via `@paginate(type: CURSOR)`.

**Redis caching**
Cache the first page of the feed (highest traffic, tolerates brief staleness). Lighthouse supports field-level caching via `@cache(maxAge: 60)` with no code changes.

**Read replicas**
Route reads to a replica via Laravel's native `read/write` connection config. Zero application code changes.

**Partitioning**
At 10M+ rows, partition posts by month. The feed queries recent data — MySQL scans one partition instead of the full table.

**Fan-out on write**
At true scale, materialise each user's feed in Redis as a sorted set. Reads become a single key lookup. This is what Twitter and Instagram use — overkill below ~100k active users but the right answer at scale.

Before any of the above: run `EXPLAIN` on slow queries first. Never optimise blind.