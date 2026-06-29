# AGENTS.md

## Project context

This repository is a Laravel application for document ingestion, chunking,
embedding-based search, and FAQ generation. Treat database integrity,
background jobs, file handling, and external AI/embedding API failures as
primary reliability risks.

## Review guidelines

- Flag any change that can lose uploaded documents, chunks, embeddings, FAQs,
  or user data as P1 unless it has a clear migration, rollback, or recovery
  path.
- For migrations, verify foreign keys, cascade behavior, indexes for lookup
  paths, nullable/default choices, and compatibility with PostgreSQL and
  pgvector before approving.
- For document processing and queue code, check that jobs are idempotent:
  retrying the same job must not duplicate chunks, FAQs, charges, or status
  transitions.
- Treat missing failure states as high risk. Long-running processing must move
  documents to a failed or retryable state when parsing, storage, embedding, or
  generation fails.
- Do not approve code that stores API keys, database passwords, tokens, raw
  secrets, or private document contents in logs.
- For file uploads and parsing, verify MIME/type checks, file size limits,
  storage paths, authorization, and cleanup of partial files.
- For search and generation features, check that tenant/user authorization is
  enforced before reading documents, chunks, embeddings, or generated FAQs.
- For external API calls, require timeouts, retry limits, backoff, and clear
  behavior when rate limited or unavailable.
- For database queries over chunks, embeddings, or documents, check that common
  read paths have appropriate indexes and do not load unbounded result sets into
  memory.
- For public routes and controllers, verify validation, CSRF/auth middleware,
  authorization checks, and safe error responses.
- Prefer small, boring, observable changes over clever rewrites. Flag changes
  that make operations harder to monitor, retry, or roll back.
- If a PR changes behavior, expect a focused test or an explicit reason why the
  behavior cannot be tested yet.

## Verification commands

- PHP formatting: `./vendor/bin/pint --test`
- Backend tests: `php artisan test`
- Frontend build: `npm run build`
