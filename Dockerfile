#
# Composer
#
FROM composer:1.8 AS vendor

COPY composer.* .

RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --no-dev \
    && composer dump-autoload -o --no-dev

#
# Extenstions
#
FROM php:7.3-cli AS ed25519-ext

RUN docker-php-source extract \
    && apt update \
    && apt install -y --no-install-recommends git libssl-dev \
    && apt autoclean \
    && rm -rf /var/lib/apt/lists/*

RUN git clone https://github.com/encedo/php-ed25519-ext.git \
    && cd php-ed25519-ext \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && make test \
    && docker-php-ext-enable ed25519 \
    && docker-php-source delete

#
# Saseul
#
FROM php:7.3-fpm

RUN apt update \
    && apt install -y --no-install-recommends \
            libmemcached-dev zlib1g-dev\
    && apt autoclean \
    && rm -rf /var/lib/apt/lists/*

# ext
COPY --from=ed25519-ext $PHP_INI_DIR/conf.d/* $PHP_INI_DIR/conf.d/
COPY --from=ed25519-ext /usr/local/lib/php/extensions/no-debug-non-zts-20180731/* \
            /usr/local/lib/php/extensions/no-debug-non-zts-20180731/

RUN docker-php-source extract \
    && pecl install xdebug-2.7.2 memcached mongodb ast \
    && docker-php-ext-enable xdebug memcached mongodb ast \
    && docker-php-ext-install -j$(nproc) pcntl json \
    && docker-php-source delete

# User settings
WORKDIR /app/saseul

COPY . .
COPY ./conf/php.ini $PHP_INI_DIR/php.ini

# Todo (SC-98): change group names from saseul-node to saseul.
RUN groupadd saseul-node \
    && useradd -m -s /bin/bash -G saseul-node,www-data saseul \
    && chown -Rf saseul.saseul-node /app/saseul

USER saseul:saseul-node

COPY --from=vendor /app/vendor .
