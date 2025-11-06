# AIProjectRep

## Project Purpose
AIProjectRep is the PHP 8.2 + Slim 4 port of the original TypeScript single-page app that showcases curated AI side projects, article highlights, and an AI prompt generator. The backend exposes a minimal REST surface for the frontend to fetch content, persist new project ideas, and proxy OpenAI chat completions, while the static assets in `public/` deliver the migrated user interface.

### TypeScript Migration Source
The PHP implementation was migrated from the TypeScript codebase outlined in [`docs/migration.md`](docs/migration.md). Key modules that informed this port include:

- `src/main.ts` (SPA bootstrapper and router)
- `src/api/client.ts` (Axios HTTP client)
- `src/modules/projects.ts` (project CRUD flows)
- `src/modules/generator.ts` (prompt submission logic)
- `src/pages/Home.tsx` plus supporting `src/components/*` (UI composition)

These modules were converted into the Slim routes, controllers, services, and the static assets that live under `public/`.

## Prerequisites
Ensure the following are installed locally before working with the project:

- **PHP 8.2** with the `pdo`, `json`, and `curl` extensions enabled.
- **Composer** (v2 recommended) for dependency management.

## Local Setup
1. **Install PHP dependencies**
   ```bash
   composer install
   ```
2. **Copy environment configuration**
   ```bash
   cp .env.example .env
   ```
   Populate the values as needed (see [Environment Variables](#environment-variables)).
3. **Place frontend assets**
   The migrated assets already live in `public/assets/` (`main.js`, `styles.css`). If you rebuild or customise the frontend, ensure any compiled JS/CSS is written back into this directory so PHP's built-in server can serve them directly.
4. **Launch the local server**
   ```bash
   php -S 0.0.0.0:8000 -t public public/index.php
   ```
   Alternatively, run `composer start` to execute the same command via the Composer script.
5. **Visit the app**
   Open <http://localhost:8000> to load the frontend. The UI will call the `/api/...` endpoints documented below.

### Optional Build Steps
If you retain the original TypeScript sources for experimentation:

- Run your bundler (e.g., Vite/Rollup) to emit ES modules and copy them into `public/assets/`.
- Use `npm run build` (or the equivalent) to regenerate CSS/JS, then overwrite the shipped `public/assets/main.js` and `public/assets/styles.css` files.
- Verify any asset fingerprints referenced in `public/index.html` remain accurate after rebuilding.

## API Reference
All API responses share the JSON envelope produced by `JsonResponder`:

```json
{
  "success": true,
  "data": { /* endpoint-specific payload */ }
}
```

Errors respond with `success: false`, a `message`, and optional `errors` array.

### `GET /api/health`
- **Purpose:** Readiness probe used by uptime monitors and deployment smoke checks.
- **Query parameters:** none.
- **Sample response:**
  ```json
  {
    "success": true,
    "data": {
      "status": "ok",
      "timestamp": "2024-02-18T10:22:31+00:00"
    }
  }
  ```
- **Frontend usage:** Not called by the browser bundle; used manually or by infrastructure checks.

### `GET /api/projects`
- **Purpose:** Fetch the curated catalogue rendered in the project grid.
- **Query parameters:** none.
- **Sample response:**
  ```json
  {
    "success": true,
    "data": {
      "projects": [
        {
          "title": "Autonomous Research Assistant",
          "summary": "An AI agent that synthesises research papers into structured briefs.",
          "tags": ["nlp", "summarisation", "agent"],
          "createdAt": "2024-01-12T09:00:00+00:00"
        }
      ]
    }
  }
  ```
- **Frontend usage:** `public/assets/main.js` loads this endpoint on page init to render the cards. A `success: false` response surfaces a fallback error banner in the grid.

### `POST /api/projects`
- **Purpose:** Persist a new project idea originating from the generator panel.
- **Headers:** `Content-Type: application/json`.
- **Body parameters:**
  - `title` (string, required)
  - `summary` (string, required)
  - `tags` (array of strings, required)
- **Sample request:**
  ```http
  POST /api/projects HTTP/1.1
  Content-Type: application/json

  {
    "title": "Workflow Copilot",
    "summary": "AI agent that spots manual bottlenecks and suggests automations.",
    "tags": ["productivity", "automation"]
  }
  ```
- **Sample success response:**
  ```json
  {
    "success": true,
    "data": {
      "project": {
        "title": "Workflow Copilot",
        "summary": "AI agent that spots manual bottlenecks and suggests automations.",
        "tags": ["productivity", "automation"],
        "createdAt": "2024-02-18T10:23:11+00:00"
      }
    }
  }
  ```
- **Validation errors:** Missing required fields return HTTP 422 with `success: false` and a descriptive message.
- **Frontend usage:** The generator modal posts here on submission, then optimistically prepends the response to the grid.

### `POST /api/ask`
- **Purpose:** Proxy OpenAI chat completions for the idea generator.
- **Headers:** `Content-Type: application/json`, `Authorization: Bearer <OPENAI_API_KEY>` is added server-side via `AiClient`.
- **Body parameters:**
  - `prompt` (string, required)
  - `systemPrompt` (string, optional, default: "You are a helpful assistant.")
- **Sample request:**
  ```http
  POST /api/ask HTTP/1.1
  Content-Type: application/json

  {
    "prompt": "List three AI project ideas for climate tech startups."
  }
  ```
- **Sample success response:**
  ```json
  {
    "success": true,
    "data": {
      "response": {
        "id": "chatcmpl-abc123",
        "choices": [
          {
            "message": {
              "role": "assistant",
              "content": "1. Carbon capture analytics..."
            }
          }
        ]
      }
    }
  }
  ```
- **Frontend usage:** The generator modal displays the first choice message. Network or auth failures should be bubbled to the user as a toast.

### `GET /api/articles`
- **Purpose:** Retrieve curated article teasers from the configured content API.
- **Query parameters:** none.
- **Sample response:**
  ```json
  {
    "success": true,
    "data": {
      "articles": [
        {
          "title": "How agents transform research",
          "url": "https://example.com/articles/agents",
          "publishedAt": "2024-02-10"
        }
      ]
    }
  }
  ```
- **Frontend usage:** `main.js` renders the article list in the right-hand column. Empty arrays show the default "Check back soon" placeholder.

## Environment Variables
The `.env.example` file documents the configuration expected at runtime:

| Variable | Description |
| --- | --- |
| `APP_ENV` | Runtime environment label (e.g., `development`, `production`). |
| `APP_DEBUG` | Enables verbose error output when `true`. Disable in production. |
| `OPENAI_API_KEY` | Secret key injected into outbound requests made by `AiClient`. Required for `/api/ask`. |
| `CONTENT_API_BASE` | Base URL for the external content service queried by `ContentService`. |

Environment variables are loaded in `public/index.php` via `vlucas/phpdotenv`. When deploying, ensure the `.env` file is present or variables are injected by your hosting platform.

## Testing & Smoke Checks
The project currently relies on manual verification:

1. **Static analysis / syntax check**
   ```bash
   php -l public/index.php src/**/*.php
   ```
   (Run across key entry points to catch parse errors.)
2. **HTTP smoke tests**
   After starting the server, issue curl probes:
   ```bash
   curl http://localhost:8000/api/health
   curl http://localhost:8000/api/projects
   ```
   Add authenticated requests to `/api/ask` once `OPENAI_API_KEY` is configured.
3. **Frontend checks**
   Load the homepage and ensure projects, articles, and generator flows operate as expected. Monitor browser console for errors.

Consider introducing PHPUnit tests under a `tests/` directory for controller/service coverage in future iterations.

## Deployment & Release Tagging
- **Document root:** Point your web server to the `public/` directory so `public/index.php` handles all requests.
- **Environment configuration:** Provide production-ready `.env` values (disable `APP_DEBUG`, supply secrets, adjust `CONTENT_API_BASE`).
- **Build artifacts:** If you recompile frontend assets, commit or package the final files under `public/assets/`.
- **Release tagging:** Create annotated Git tags (e.g., `git tag -a v1.0.0 -m "Initial PHP port"`) whenever deploying a new version. Push with `git push origin --tags` to keep deployment history traceable.
- **Post-deploy smoke tests:** Run `/api/health` and `/api/projects` on the live environment to confirm availability before announcing the release.

