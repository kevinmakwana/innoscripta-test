# Scaling and Performance Guide

This guide covers performance considerations, bottlenecks, and scaling strategies for the news aggregator backend. It assumes a Laravel application with queued jobs, database storage, and external API integrations.

## Performance Benchmarks (Estimated)

- **Article Fetch**: 1-5 seconds per source (API latency + processing).
- **Normalization/Deduplication**: <100ms per article.
- **API Response**: <500ms for paginated queries (with indexing).
- **Concurrent Users**: 1000+ with proper caching/optimization.

## Key Performance Considerations

### Database Optimization
- **Indexing**: Ensure indexes on `articles.published_at`, `articles.source_id`, `articles.category_id`, `articles.author_id`. Add full-text index on `title` and `excerpt` for search.
- **Query Optimization**: Use eager loading (`with()`) to avoid N+1 queries. Paginate results (default 15-100 items).
- **Connection Pooling**: For MySQL, configure connection limits in `config/database.php`.
- **Read Replicas**: For high-read loads, add read replicas and route queries accordingly.

### Queue and Job Performance
- **Worker Scaling**: Increase queue workers (`php artisan queue:work`) based on load. Use `--max-jobs` and `--sleep` for tuning.
- **Batching**: Group jobs (e.g., fetch multiple sources in one batch) to reduce overhead.
- **Rate Limiting**: Implement in adapters to avoid API throttling (e.g., NewsAPI limits).
- **Job Isolation**: Each job processes one source to prevent failures from cascading.

### Caching Strategies
- **Response Caching**: Cache API responses for 5-15 minutes using Redis.
- **Model Caching**: Cache categories/authors/sources if static.
- **Fragment Caching**: Cache expensive queries (e.g., personalized articles).

### External API Performance
- **Timeout Handling**: Set short timeouts (5-10s) on HTTP clients to fail fast.
- **Retry Logic**: Exponential backoff for transient failures.
- **Circuit Breaker**: Disable sources after repeated failures to prevent wasted requests.

### Memory and CPU
- **Job Memory**: Monitor for memory leaks in long-running jobs; restart workers periodically.
- **PHP Optimization**: Use OPcache, JIT (PHP 8+), and tune `memory_limit` in `php.ini`.
- **Container Limits**: Set CPU/memory limits in Docker/K8s to prevent resource exhaustion.

## Scaling Strategies

### Horizontal Scaling
- **Workers**: Add more queue workers across servers/instances.
- **Application Servers**: Load balance web requests with Nginx/HAProxy.
- **Database Sharding**: Shard articles by date/source if volume >1M records.

### Vertical Scaling
- **Increase Resources**: More CPU/RAM for intensive jobs (e.g., normalization).
- **Database Upgrades**: Larger MySQL instances for higher concurrency.

### Microservices Evolution
- **Split Components**: Move adapters to separate services for independent scaling.
- **Event-Driven**: Use message brokers for inter-service communication.
- **API Gateway**: Add for routing and rate limiting.

### Monitoring and Alerts
- **Metrics**: Track job success/failure rates, API response times, DB query performance.
- **Tools**: Laravel Telescope for debugging; Prometheus/Grafana for monitoring.
- **Alerts**: Notify on high queue depth, failed jobs, or slow queries.

## Load Testing

Use tools like Artillery or k6 to simulate:
- 100 concurrent users fetching articles.
- High job throughput (e.g., 1000 articles/min).

Example k6 script:
```javascript
import http from 'k6/http';

export default function () {
  http.get('http://localhost:8000/api/v1/articles');
}
```

## Common Bottlenecks and Fixes

- **Slow Queries**: Add indexes; use EXPLAIN to analyze.
- **Queue Backlog**: Increase workers; optimize job logic.
- **API Timeouts**: Implement retries; cache responses.
- **Memory Issues**: Profile with Blackfire; optimize loops.

## Production Deployment Checklist

- [ ] Enable OPcache and JIT.
- [ ] Configure Redis for caching/queues.
- [ ] Set up monitoring (e.g., Laravel Horizon for queues).
- [ ] Use CDN for static assets (if any).
- [ ] Implement database backups and failover.
- [ ] Test with realistic data volumes.

For detailed implementation, see `docs/ARCHITECTURE.md` and `docs/OPERATION.md`.</content>
<parameter name="filePath">/Volumes/Personal/innoscripta-test/docs/SCALING_PERFORMANCE.md