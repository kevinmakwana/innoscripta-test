### Build stage: install PHP extensions and Composer deps to speed up later builds
FROM --platform=$BUILDPLATFORM php:8.4-cli AS build

# Build stage: install system deps and composer prod dependencies. This stage
# installs only production deps (no-dev) and provides vendor/ to subsequent
# stages to avoid re-installing dependencies and to keep the final image small.
ARG COMPOSER_HOME=/tmp/composer

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    zlib1g-dev \
    libicu-dev \
    libxml2-dev \
    libsqlite3-dev \
    default-mysql-client \
    libmariadb-dev-compat \
    libmariadb-dev \
    ca-certificates \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql zip mbstring exif pcntl bcmath gd intl sockets \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# copy composer files first to take advantage of layer caching
COPY composer.json composer.lock ./

# Install production dependencies only in the build stage. This keeps the
# subsequent stages fast and allows us to copy a vendor/ tree instead of
# re-running a full composer install in each stage.
RUN composer install --no-dev --prefer-dist --no-scripts --no-interaction --no-progress --optimize-autoloader

### Source stage: copy application source for better layer caching
FROM build AS source

# Copy application source separately to allow vendor layer reuse
COPY . .

### Dev stage: for local development (with composer and tools available)
FROM php:8.4-cli AS dev
LABEL org.opencontainers.image.source="innoscripta-test"

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    zlib1g-dev \
    libicu-dev \
    libxml2-dev \
    libsqlite3-dev \
    default-mysql-client \
    libmariadb-dev-compat \
    libmariadb-dev \
    ca-certificates \
    procps \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql zip mbstring exif pcntl bcmath gd intl sockets \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# create developer user for convenience
RUN useradd -G www-data,root -u 1000 -d /home/developer developer || true
RUN mkdir -p /home/developer && chown developer:developer /home/developer
USER developer

ENV PATH="/home/developer/.composer/vendor/bin:${PATH}"

# Copy vendor produced by the build stage (production deps). This avoids
# a full re-install of production dependencies and speeds up local builds.
# Copy application source from source stage first
COPY --chown=developer:developer --from=source /app /var/www/html

# Then copy vendor produced by the build stage to ensure the image finally
# contains vendor/ even if source copy would otherwise change files.
COPY --chown=developer:developer --from=build /app/vendor /var/www/html/vendor

# For CI debugging: show vendor contents during build logs (harmless)
RUN ls -la /var/www/html/vendor || true

# Ensure dev dependencies are available in dev image; composer will be fast
# because vendor cache is present. Use the composer's cache mounted by docker-compose
# when available.
RUN composer install --no-interaction --prefer-dist --no-scripts || true

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

### Production stage: minimal image
FROM php:8.4-fpm AS prod

# Install only runtime/build dependencies to build needed extensions on Debian
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    zlib1g-dev \
    libicu-dev \
    libxml2-dev \
    gcc \
    g++ \
    make \
    autoconf \
    pkg-config \
    ca-certificates \
    && docker-php-ext-install pdo pdo_mysql zip mbstring exif pcntl bcmath gd intl sockets \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy application and vendor from source stage
COPY --from=source /app /var/www/html

# Create non-root user for security (Debian-friendly)
RUN groupadd -g 1000 www-data || true && \
    useradd -u 1000 -r -g www-data -d /var/www/html -s /usr/sbin/nologin www-data || true && \
    chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
