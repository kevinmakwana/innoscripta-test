# Contributing to Innoscripta Test

Thank you for your interest in contributing! This document outlines the guidelines for contributing to this Laravel backend scaffold.

## Getting Started

1. **Fork the Repository**: Create a fork on GitHub.
2. **Clone Locally**: `git clone https://github.com/your-username/innoscripta-test.git`
3. **Set Up Environment**: Follow the README for Docker setup.
4. **Run Tests**: Ensure all tests pass: `docker compose exec app ./vendor/bin/phpunit`

## Development Workflow

1. **Create a Branch**: `git checkout -b feature/your-feature`
2. **Make Changes**: Follow PSR-12 standards and add tests.
3. **Run Linting**: `docker compose exec app ./vendor/bin/phpstan analyse`
4. **Commit**: Use clear, descriptive messages.
5. **Push and PR**: Push to your fork and create a pull request.

## Code Standards

- **PHP**: PSR-12 formatting.
- **Tests**: PHPUnit with feature and unit tests.
- **Commits**: Conventional commits (e.g., `feat: add new adapter`).

## Adding Adapters

- Implement `SourceAdapterInterface`.
- Add to `AdapterFactory` or use DI.
- Include tests and update OpenAPI if needed.

## Reporting Issues

- Use GitHub Issues with clear descriptions and steps to reproduce.
- Include environment details (PHP version, Laravel version).

## Questions?

Reach out via GitHub Discussions or email the maintainers.