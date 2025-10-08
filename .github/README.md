# CI & Packaging notes for maintainers

This file explains the GitHub Actions workflows used in this repository and the secrets/permissions required to run the Docker Compose CI that uses Buildx layer caching via GHCR.

Workflows
- `.github/workflows/docker-compose-ci.yml` — builds the repository's Docker Compose stack on `ubuntu-latest`, builds the `app` image via Buildx (uses GHCR for layer cache), runs `phpstan` and `phpunit` inside the `app` container, and uploads junit results.

Required permissions & secrets
- `GITHUB_TOKEN` (automatically provided) — the workflow uses this token to authenticate against GHCR for pushing/reading the build cache. The workflow requires `packages: write` permission in the job to allow cache writes.
- `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` — optional: if you prefer Docker Hub for cache/push you can set these secrets and update the workflow to log in to Docker Hub instead.

Organization / repo settings
- Ensure GitHub Packages (GHCR) is enabled for your org and that repo-level `GITHUB_TOKEN` has permission to write packages. If your org restricts token privileges, create a Personal Access Token (PAT) with `packages:write` and store it as a repository secret (for example `GHCR_PAT`) and update the workflow to use it instead of `GITHUB_TOKEN`.

Troubleshooting
- If builds always miss cache, confirm that:
  - The workflow job includes `permissions: packages: write` (we set this by default in the workflow).
  - GHCR retention or policies haven't expired or blocked cache tags.
  - The runner can reach GHCR (no egress network restrictions).
- If cache uploads fail with authentication errors, swap to a PAT with packages:write scope and set as repository secret.

Contact
- For CI-related issues, open an issue in the repo and tag the maintainers. Include the CI job logs and the workflow run ID to help triage.
