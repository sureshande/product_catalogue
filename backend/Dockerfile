# Laravel API image — also used for the queue worker (different command).
FROM php:8.4-cli-alpine

RUN apk add --no-cache unzip \
    && docker-php-ext-install pdo_mysql

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Dependencies first so this layer is cached between code changes.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

# PHP's built-in server with several workers is enough for a local PoC.
# In AWS this becomes php-fpm + nginx (or FrankenPHP/Octane) on ECS — see docs/PLAN.md.
ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 8000

ENTRYPOINT ["sh", "/app/docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
