# Test Summary

Generated: 2025-10-06 11:45:04

## Overall

- Total tests: 67
- Total assertions: 391
- Failures: 0
- Errors: 0
- Skipped: 0
- Total time: 1.613325s

## Top-level suites

- Unit: 21 tests, 98 assertions, time 0.981881s
- Feature: 46 tests, 293 assertions, time 0.631444s

## Per-class highlights (top time contributors)

- Tests\Unit\DeduplicationServiceTest — 3 tests, 3 assertions, time 0.823736s
- Tests\Feature\CategoryEndpointsTest — 16 tests, 91 assertions, time 0.200615s
- Tests\Feature\ArticleEndpointsTest — 11 tests, 139 assertions, time 0.174312s
- Tests\Feature\PersonalizedArticlesTest — 9 tests, 32 assertions, time 0.143124s
- Tests\Unit\ArticleNormalizationEdgeCaseTest — 2 tests, 9 assertions, time 0.063244s
- Tests\Feature\FetchSourceJobFailureTest — 2 tests, 3 assertions, time 0.027864s
- Tests\Feature\AuthorTest — 2 tests, 6 assertions, time 0.024488s
- Tests\Unit\NytAdapterRetryTest — 1 tests, 2 assertions, time 0.018277s
- Tests\Feature\UserPreferenceTest — 1 tests, 7 assertions, time 0.016916s
- Tests\Unit\Integrations\NewsApiAdapterTest — 2 tests, 4 assertions, time 0.012979s
- Tests\Feature\FetchSourceJobTest — 1 tests, 6 assertions, time 0.012892s
- Tests\Unit\ArticleNormalizationStrictTest — 3 tests, 43 assertions, time 0.012488s
- Tests\Unit\GuardianAdapterTest — 1 tests, 2 assertions, time 0.011253s
- Tests\Unit\HttpRetryMiddlewarePublicContractTest — 1 tests, 2 assertions, time 0.011032s
- Tests\Feature\AuthorEndpointsTest — 1 tests, 4 assertions, time 0.011006s
- Tests\Unit\FetchMergeTest — 1 tests, 7 assertions, time 0.010117s
- Tests\Unit\NewsApiAdapterRetryCountTest — 1 tests, 3 assertions, time 0.009016s
- Tests\Feature\ExampleTest — 1 tests, 1 assertions, time 0.008213s
- Tests\Feature\HealthTest — 1 tests, 2 assertions, time 0.006075s
- Tests\Feature\OpenApiSpecTest — 1 tests, 2 assertions, time 0.005942s

## Top 10 slowest test cases

| Rank | Test case | File | Time (s) |
|------|-----------|-----:|---------:|
| 1 | test_detects_duplicate_by_external_id | /var/www/html/tests/Unit/DeduplicationServiceTest.php | 0.801310 |
| 2 | test_normalize_handles_missing_author_and_category | /var/www/html/tests/Unit/ArticleNormalizationEdgeCaseTest.php | 0.058888 |
| 3 | it_can_list_articles | /var/www/html/tests/Feature/ArticleEndpointsTest.php | 0.031067 |
| 4 | it_can_paginate_articles | /var/www/html/tests/Feature/ArticleEndpointsTest.php | 0.027527 |
| 5 | it_paginates_personalized_articles | /var/www/html/tests/Feature/PersonalizedArticlesTest.php | 0.021066 |
| 6 | authenticated_user_can_create_category | /var/www/html/tests/Feature/CategoryEndpointsTest.php | 0.020390 |
| 7 | test_fetchTopHeadlines_handles_transient_failure_and_returns_empty | /var/www/html/tests/Unit/NytAdapterRetryTest.php | 0.018277 |
| 8 | it_filters_articles_by_preferred_categories | /var/www/html/tests/Feature/PersonalizedArticlesTest.php | 0.017928 |
| 9 | test_crud_preferences | /var/www/html/tests/Feature/UserPreferenceTest.php | 0.016916 |
| 10 | it_filters_articles_by_preferred_authors | /var/www/html/tests/Feature/PersonalizedArticlesTest.php | 0.016468 |

> Note: times are taken from the junit XML supplied to this script.

## Notes & next actions

- Consider focusing optimization on the slowest classes/tests above (DB indexes, query improvements, or test isolation).
- This file is generated automatically during CI.
