# Services

This directory contains business logic services for the application.

## ArticleNormalizationService

Normalizes raw article data into standardized `NormalizedArticle` DTOs.

### Usage

```php
$normalized = app(ArticleNormalizationService::class)->normalize($rawData);
```

### Integration Examples

For a new adapter, ensure data is passed in a consistent format:

```php
// In your adapter
return [
    'title' => $data['headline'],
    'content' => $data['body'],
    'author' => $data['byline'],
    // etc.
];
```

Then, normalization handles mapping and validation.

## DeduplicationService

Handles merging and deduplication of articles.

### Usage

```php
$article = app(DeduplicationService::class)->persistOrMerge($normalizedData);
```

## Messaging Services

See `docs/MESSAGING.md` for broker abstractions (Redis, RabbitMQ).