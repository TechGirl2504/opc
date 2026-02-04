FROM composer:2.7 AS vendor
WORKDIR /app

# Install PHP deps (no dev) with optimized autoloader
COPY backend/composer.json backend/composer.lock ./
RUN composer install \
  --no-dev \
  --no-interaction \
  --no-progress \
  --prefer-dist \
  --no-scripts \
  --optimize-autoloader


FROM php:8.2-apache-bookworm AS runtime
WORKDIR /var/www/html

# System deps + PHP extensions commonly needed by Laravel
RUN set -eux; \
  apt-get update -o Acquire::Retries=3; \
  apt-get install -y --no-install-recommends \
    $PHPIZE_DEPS \
    ca-certificates \
    curl \
    libzip-dev \
    zlib1g-dev \
    libsqlite3-dev \
    unzip; \
  docker-php-ext-install -j"$(nproc)" \
    pdo \
    pdo_mysql \
    pdo_sqlite \
    zip; \
  a2enmod rewrite headers; \
  apt-get purge -y --auto-remove $PHPIZE_DEPS; \
  rm -rf /var/lib/apt/lists/*

# Configure Apache to serve Laravel public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
  && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy app source (no .env baked in)
COPY backend/ ./

# Bring in vendor from composer stage
COPY --from=vendor /app/vendor ./vendor

# Laravel writable dirs
RUN chown -R www-data:www-data storage bootstrap/cache \
  && chmod -R 775 storage bootstrap/cache

# Basic runtime defaults (override in Coolify)
ENV APP_ENV=production \
  APP_DEBUG=false \
  LOG_CHANNEL=stack

HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
  CMD curl -fsS http://localhost/up || exit 1

EXPOSE 80

