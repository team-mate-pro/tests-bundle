FROM php:8.3-cli-alpine AS base

RUN apk --update add --no-cache \
    libpng \
    libzip \
    icu \
    libxml2 \
    && apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libzip-dev \
    icu-dev \
    libxml2-dev \
    && docker-php-ext-install zip intl simplexml \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Install Bash and Composer
RUN apk --update add --no-cache bash git \
    && wget -O /usr/local/bin/composer https://getcomposer.org/composer-stable.phar \
    && chmod +x /usr/local/bin/composer

RUN git config --global --add safe.directory /app

FROM base AS bash

WORKDIR /app

COPY . .

# Install Composer dependencies, including dev dependencies for testing
RUN composer install --no-ansi --no-interaction --no-progress --optimize-autoloader

# Add vendor/bin to PATH
ENV PATH="/app/vendor/bin:${PATH}"

ENTRYPOINT ["bash"]

FROM bash AS lib

COPY --from=bash /app /app

ENTRYPOINT ["composer"]

FROM lib AS static_analysis
