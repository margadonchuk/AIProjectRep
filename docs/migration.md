# Migration Guide: TypeScript/Node to PHP 8.2 + Slim 4

This document records the architectural inventory of the original TypeScript project and explains how each concern was migrated into the PHP application. Use it as the reference point for future parity checks or incremental rewrites.

## Application Architecture

### Original TypeScript layout (summary)

| Area | Description |
| --- | --- |
| `src/main.ts` | Bootstrapped the SPA, mounted the router, and hydrated global stores for projects, articles, and AI prompts. |
| `src/api/client.ts` | Axios wrapper that injected the `API_BASE_URL` env value and exposed helpers such as `getProjects()`, `createProject()`, `getArticles()`, and `askAi()`. |
| `src/modules/projects.ts` | Encapsulated project CRUD operations and emitted events consumed by the UI. |
| `src/modules/generator.ts` | Managed prompt submission to `/api/ask`, normalised responses, and surfaced optimistic UI state. |
| `src/pages/Home.tsx` | Primary route that rendered the hero, project cards, article feed, and modal generator panel. |
| `src/components/*` | Stateless UI components (cards, tag lists, loaders) consumed by the home page. |

### PHP Slim 4 layout

| Path | Role |
| --- | --- |
| `public/index.php` | Front-controller that loads environment variables, builds the Slim app, registers middleware, and loads routes. 【F:public/index.php†L1-L34】|
| `src/routes.php` | Centralised route declarations and lightweight composition root for repositories and services. 【F:src/routes.php†L1-L37】|
| `src/Controllers/*` | HTTP controllers implementing the API surface (`ProjectController`, `AiController`, `ContentController`, `HealthController`). 【F:src/Controllers/ProjectController.php†L1-L45】【F:src/Controllers/AiController.php†L1-L42】|
| `src/Services/*` | Domain services for persistence (`ProjectRepository`) and external integrations (`AiClient`, `ContentService`). 【F:src/Services/ProjectRepository.php†L1-L43】【F:src/Services/AiClient.php†L1-L53】|
| `src/Support/*` | Cross-cutting helpers such as payload validation and JSON response formatting. 【F:src/Support/Validator.php†L1-L32】【F:src/Support/JsonResponder.php†L1-L34】|
| `public/index.html` | Static entry point mirroring the SPA shell with panels for projects, articles, and the idea generator. 【F:public/index.html†L1-L44】|
| `public/assets/main.js` | ES6 module converted from the TypeScript frontend modules; handles DOM rendering and API calls. 【F:public/assets/main.js†L1-L84】|
| `public/assets/styles.css` | Port of the Tailwind-inspired theme to static CSS. 【F:public/assets/styles.css†L1-L126】|
| `data/projects.json` | File-backed store for persisted project cards (replacing the Node in-memory array). 【F:data/projects.json†L1-L12】|

## Route Inventory

| HTTP Method & Path | Responsibility | Source PHP handler |
| --- | --- | --- |
| `GET /api/health` | Lightweight readiness probe replacing the original Express health check. | `HealthController::__invoke()` 【F:src/Controllers/HealthController.php†L1-L17】|
| `GET /api/projects` | Returns the curated project catalogue consumed by the grid view. | `ProjectController::list()` 【F:src/Controllers/ProjectController.php†L16-L23】|
| `POST /api/projects` | Accepts JSON payloads to append new concepts (parity with the TS `createProject`). | `ProjectController::create()` 【F:src/Controllers/ProjectController.php†L25-L44】|
| `POST /api/ask` | Re-implements the OpenAI chat completion proxy using Guzzle. | `AiController::chat()` + `AiClient::chat()` 【F:src/Controllers/AiController.php†L17-L39】【F:src/Services/AiClient.php†L20-L47】|
| `GET /api/articles` | Fetches editorial notes from an external CMS/API. | `ContentController::articles()` + `ContentService::fetchArticles()` 【F:src/Controllers/ContentController.php†L15-L20】【F:src/Services/ContentService.php†L14-L35】|

## Data Contracts

### Project payload

```json
{
  "title": "Autonomous Research Assistant",
  "summary": "An AI agent that synthesises research papers into structured briefs.",
  "tags": ["nlp", "summarisation", "agent"],
  "createdAt": "2024-01-12T09:00:00+00:00"
}
```

* **Shape**: `title` (string), `summary` (string), `tags` (array of strings), `createdAt` (ISO date string).
* **Validation**: enforced through `Validator::requireFields()` during POST requests. 【F:src/Controllers/ProjectController.php†L30-L41】【F:src/Support/Validator.php†L11-L27】
* **Storage**: appended to `data/projects.json`. 【F:src/Services/ProjectRepository.php†L27-L42】

### AI generator request/response

* **Request** (JSON): `{ "prompt": string, "systemPrompt?": string }`
* **Response** (JSON): `{ "success": true, "data": { "id": string, "choices": Array<ChatCompletionChoice> } }`
* **Implementation**: proxied to OpenAI via `AiClient`, including HTTP error handling. 【F:src/Services/AiClient.php†L20-L47】
* **Frontend consumption**: `main.js` renders the first choice into the generator panel. 【F:public/assets/main.js†L18-L47】

### Article feed

* **Response shape**: `{ "success": true, "data": { "articles": Array<{ title: string, url: string, publishedAt?: string }> } }`
* **Source**: `ContentService::fetchArticles()` hydrates from `CONTENT_API_BASE`. 【F:src/Services/ContentService.php†L17-L35】
* **Rendering**: `main.js` composes the list view. 【F:public/assets/main.js†L59-L83】

## Migration Mapping

| Was (TS/Node) | Becomes (PHP) |
| --- | --- |
| Express router in `server/index.ts` handling `/api/*` endpoints | Slim routes inside `src/routes.php` with dedicated controllers. 【F:src/routes.php†L16-L35】|
| Axios API client (`src/api/client.ts`) | Native `fetch` wrappers in `public/assets/main.js` and backend Guzzle clients (`AiClient`, `ContentService`). 【F:public/assets/main.js†L1-L84】【F:src/Services/AiClient.php†L1-L53】|
| File-based repository utilities in `server/storage.ts` | `ProjectRepository` JSON persistence service. 【F:src/Services/ProjectRepository.php†L1-L43】|
| React/Tailwind single page shell | Static `public/index.html` plus vanilla JS module and handcrafted CSS. 【F:public/index.html†L1-L44】【F:public/assets/styles.css†L1-L126】|
| OpenAI proxy implemented with `node-fetch` | `AiClient` built on top of Guzzle with Slim controller orchestration. 【F:src/Controllers/AiController.php†L17-L39】【F:src/Services/AiClient.php†L20-L47】|
| CMS fetch helper (`src/api/content.ts`) | `ContentService` + `/api/articles` endpoint. 【F:src/Services/ContentService.php†L14-L35】|

## Frontend adjustments

* Converted TypeScript modules to native ES modules with optional chaining and template literals to simplify bundling. 【F:public/assets/main.js†L1-L84】
* Updated API URLs to target `/api/...` paths served by Slim instead of the Node server base URL. 【F:public/assets/main.js†L20-L22】【F:public/assets/main.js†L37-L66】
* Moved static assets (`main.js`, `styles.css`) into `public/assets/` for direct serving by PHP's built-in server. 【F:public/index.html†L6-L43】

## Operations notes

* Boot locally with `composer start` (uses PHP's built-in server). 【F:composer.json†L6-L22】
* Copy `.env.example` to `.env` and fill provider credentials before invoking AI/chat functionality. 【F:.env.example†L1-L4】
* All routes run through `public/index.php`, so deploy the `/public` directory as the document root in production environments. 【F:public/index.php†L5-L34】
