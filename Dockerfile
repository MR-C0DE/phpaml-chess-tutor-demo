FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl git libssl-dev pkg-config unzip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN mkdir -p /tmp/phpaml-framework runtime/framework \
    && curl -fsSL https://github.com/MR-C0DE/phpaml-framework/archive/refs/tags/v0.2.1-beta.1.tar.gz \
       | tar -xz -C /tmp/phpaml-framework --strip-components=1 \
    && cp -R /tmp/phpaml-framework/src/. runtime/framework/ \
    && rm -rf /tmp/phpaml-framework \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY . .

RUN mkdir -p runtime/storage runtime/cache runtime/tmp \
    && chmod -R 775 runtime/storage runtime/cache runtime/tmp

ENV APP_ENV=production
ENV APP_DEBUG=false
EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public public/index.php"]
