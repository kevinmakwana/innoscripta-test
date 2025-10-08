# Documentation checklist

When you change controller behavior or query parameters that affect API responses, follow this checklist:

- Update controllers and add/modify tests covering the changed behavior.
- Update `resources/views` or API resources if response shapes changed.
- Regenerate the OpenAPI spec (`docs/openapi.json`) to reflect new params, response schemas, and pagination meta.
- Run the `OpenAPI` validation workflow (CI) or `php artisan` doc generation tooling if available.
- Commit the updated `docs/openapi.json` and adjust CI publishing if necessary.

This keeps API docs in sync and avoids surprises for clients using the spec.
