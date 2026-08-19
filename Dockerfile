FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libssl-dev pkg-config unzip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY output/phpaml-build.zip /tmp/phpaml-build.zip
RUN unzip -q /tmp/phpaml-build.zip -d /app \
    && rm /tmp/phpaml-build.zip \
    && mkdir -p runtime/storage runtime/cache runtime/tmp \
    && chmod -R 775 runtime/storage runtime/cache runtime/tmp

ENV APP_ENV=production
ENV APP_DEBUG=false
EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public public/index.php"]
