# NoteMind - AI Powered Notes Management System

NoteMind is a simple Notes Management System built with Laravel. It allows users to create, update, delete, view, search, and summarize notes from a clean web interface. The project also includes REST APIs, MySQL support, semantic search, AI summary integration, validation, rate limiting, tests, and Docker support.

## Features

- Create, update, delete, and view notes
- Paginated notes listing
- Clean JSON REST APIs
- Server-side validation and meaningful HTTP status codes
- Semantic note search using stored vectors and cosine similarity
- AI-generated note summaries using OpenAI when an API key and API quota are available
- Offline summary fallback when the AI service is unavailable
- MySQL / PostgreSQL compatible Laravel migrations
- Rate limiting and SQL injection protection
- Docker Compose setup with Laravel and MySQL

## Tech Stack

- PHP 8.2+
- Laravel 12
- MySQL 8+ (primary setup)
- PostgreSQL compatible migrations
- Blade, JavaScript, HTML, and CSS
- OpenAI Responses API (optional, for AI summaries)
- Docker and Docker Compose

## Local Setup

### 1. Clone / open the project

```bash
git clone <your-repository-url>
cd laravep-app
```

### 2. Install dependencies

```bash
composer install
copy .env.example .env
php artisan key:generate
```

### 3. Create the MySQL database

```sql
CREATE DATABASE notemind CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Update these values in `.env` if your MySQL credentials are different:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=notemind
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Run migrations and start the app

```bash
php artisan migrate
php artisan serve
```

Open the application at `http://127.0.0.1:8000`.

## Docker Setup

Docker Compose starts both Laravel and MySQL automatically.

```bash
docker compose up --build
```

Open `http://localhost:8000` after the containers start. The application runs migrations automatically after MySQL becomes healthy.

Useful commands:

```bash
docker compose down
docker compose logs -f app
docker compose logs -f mysql
```

The MySQL data is stored in a Docker volume. Do not use `docker compose down -v` unless you intentionally want to remove all Docker database data.

## API Documentation

Base URL: `http://127.0.0.1:8000/api`

All API responses follow this structure:

```json
{
  "success": true,
  "message": "Notes retrieved.",
  "data": [],
  "meta": {}
}
```

| Method | Endpoint | Description | Success status |
|---|---|---|---|
| POST | `/notes` | Create a note | `201 Created` |
| GET | `/notes?page=1&limit=10` | Get paginated notes | `200 OK` |
| GET | `/notes/{id}` | Get one note | `200 OK` |
| PUT/PATCH | `/notes/{id}` | Update a note | `200 OK` |
| DELETE | `/notes/{id}` | Delete a note | `200 OK` |
| GET | `/notes/search?q=docker` | Search notes by meaning | `200 OK` |
| POST | `/notes/{id}/summary` | Generate a note summary | `200 OK` |

### Create a note

```http
POST /api/notes
Content-Type: application/json
```

```json
{
  "title": "Docker basics",
  "content": "Docker packages an application and its dependencies into a container."
}
```

### Get paginated notes

```http
GET /api/notes?page=1&limit=10
```

Example pagination response:

```json
{
  "success": true,
  "message": "Notes retrieved.",
  "data": [],
  "meta": {
    "page": 1,
    "limit": 10,
    "total": 25,
    "last_page": 3
  }
}
```

### Validation and error responses

- `422 Unprocessable Content` - validation error
- `404 Not Found` - note does not exist
- `429 Too Many Requests` - application rate limit exceeded

Example validation response:

```json
{
  "message": "The title field is required.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

## Database Schema

The main table is `notes`.

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Primary key |
| `title` | varchar(180) | Note title |
| `content` | text | Full note content |
| `embedding` | JSON | Internal vector used for semantic search |
| `summary` | text, nullable | Generated summary |
| `created_at` | timestamp | Creation time |
| `updated_at` | timestamp | Last update time |

The `embedding` field is intentionally hidden from public API responses because it is an internal search implementation detail.

## Architecture

The application uses a simple Laravel service-based structure:

```text
Browser UI / API Client
        |
        v
NoteController
        |
        +-- StoreNoteRequest / UpdateNoteRequest (validation)
        +-- Note model (Eloquent database access)
        +-- SemanticSearchService (vector creation and cosine ranking)
        +-- SummaryService (OpenAI summary or local fallback)
        |
        v
MySQL / PostgreSQL
```

- `NoteController` handles HTTP requests and JSON responses.
- Form Request classes validate incoming note data.
- Eloquent manages database reads and writes.
- `SemanticSearchService` creates a normalized vector for each note and ranks notes using cosine similarity.
- `SummaryService` calls OpenAI when configured. If OpenAI is unavailable, it returns a local extractive summary so the app remains usable.

## AI Integration

### AI Summary

To enable OpenAI-powered summaries, add a valid, funded API key to `.env`:

```env
OPENAI_API_KEY=your_openai_api_key
OPENAI_SUMMARY_MODEL=gpt-4.1-mini
```

Then call:

```http
POST /api/notes/{id}/summary
```

The response includes `provider: "openai"` when OpenAI generates the summary. If the API key is missing, billing quota is exhausted, or the service is unavailable, it returns `provider: "local-fallback"` and a clear warning.

### Semantic Search

Notes are converted into normalized vectors and stored in the database. A search query is converted into the same format, then the app uses cosine similarity to rank the closest notes.

For a production-scale deployment, this service can be replaced with a hosted embeddings provider plus pgvector, Pinecone, or Qdrant without changing the API contract.

## Security

- Laravel Eloquent uses parameter binding, protecting CRUD operations from SQL injection.
- All create and update requests are validated on the server.
- `title` is limited to 180 characters and `content` to 50,000 characters.
- Search and pagination input are validated and capped.
- The notes API is rate-limited to 60 requests per minute per IP address.
- The OpenAI API key stays server-side in `.env` and is not sent to the browser.
- Internal vectors are hidden from public API responses.

For a multi-user production application, Laravel Sanctum authentication should be added before public deployment.

## AI Usage Explanation

AI tools used: ChatGPT / Codex.

AI was used to speed up the initial implementation of the Laravel API structure, frontend UI, documentation, tests, semantic-search service, and Docker setup. All generated code was reviewed and adjusted manually before being used in the project.

Example prompts used:

- “Create Laravel Notes CRUD APIs with validation, pagination, clean JSON responses, and proper HTTP status codes.”
- “Build a responsive notes management UI with create, edit, delete, search, and summary actions.”
- “Design a Laravel semantic search service using stored vectors and cosine similarity.”
- “Create Docker Compose configuration for Laravel with MySQL and automatic migrations.”

Validation performed:

- Verified all Laravel routes using `php artisan route:list`.
- Ran database migrations against MySQL.
- Tested CRUD, pagination, semantic search, and summary endpoints.
- Added Laravel feature tests for note lifecycle, validation, search, summary, and pagination.
- Ran `php artisan test` successfully.
- Ran Laravel Pint formatting checks.
- Built and tested the Docker Compose environment.

## Testing

```bash
php artisan test
./vendor/bin/pint --test
```

Current automated coverage includes note creation, update, deletion, validation, summary generation, semantic search, and pagination.

## Notes for Review

- The application is available locally at `http://localhost:8000` when Docker is running.
- The API is intentionally not public by default; `localhost` is accessible only from the same computer.
- Use Docker, a cloud server, or a deployment platform to host it publicly.
