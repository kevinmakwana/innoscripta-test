![Coverage Status](https://codecov.io/gh/<your-org-or-username>/innoscripta-test/branch/main/graph/badge.svg)

# Test Coverage Reporting

This project uses PHPUnit and Codecov for test coverage reporting. Coverage is enforced in CI and must remain above 80% for all PRs.

## How Coverage Works
- Coverage is generated via PHPUnit with the `--coverage-clover` option.
- The `tools/check_coverage.php` script enforces a minimum threshold (set to 80%).
- CI will fail if coverage drops below this threshold.
- Coverage is uploaded to Codecov for badge and reporting.

## Local Coverage Run
```bash
docker compose exec app ./vendor/bin/phpunit --coverage-html build/coverage --coverage-clover build/logs/clover.xml
php tools/check_coverage.php build/logs/clover.xml
```

## Improving Coverage
- Focus on job concurrency (e.g., `FetchSourceJob`), edge cases in normalization, and error handling.
- Add tests for all branches and exception paths.
- Use mocks for external APIs and queues.

## Badge
The badge above reflects the latest coverage on the `main` branch. Replace `<your-org-or-username>` with your actual GitHub org/user for live status.
