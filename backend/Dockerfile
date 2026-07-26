FROM composer:2.8 AS vendor
WORKDIR /app
COPY . .
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

FROM dunglas/frankenphp:php8.3-alpine AS runtime

WORKDIR /app

RUN install-php-extensions \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && apk add --no-cache curl \
    && addgroup -g 10001 -S projectpulse \
    && adduser -u 10001 -S projectpulse -G projectpulse

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY Caddyfile /etc/caddy/Caddyfile

RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache \
    && chown -R projectpulse:projectpulse storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SERVER_NAME=:8000

USER projectpulse
EXPOSE 8000

HEALTHCHECK --interval=15s --timeout=3s --start-period=15s --retries=3 \
    CMD curl --fail --silent http://127.0.0.1:8000/api/health || exit 1

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
