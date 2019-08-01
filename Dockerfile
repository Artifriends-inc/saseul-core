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
    && composer dump-autoload -o

#
# Extenstions
#
FROM php:7.3-cli AS all-ext

RUN docker-php-source extract \
    && apt update \
    && apt install -y --no-install-recommends \
            git libssl-dev libmemcached-dev zlib1g-dev \
    && apt autoclean \
    && rm -rf /var/lib/apt/lists/*

RUN git clone https://github.com/encedo/php-ed25519-ext.git \
    && cd php-ed25519-ext \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && make test \
    && docker-php-ext-enable ed25519

RUN pecl install xdebug 2.7.0 \
    && docker-php-ext-enable xdebug \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && pecl install ast \
    && docker-php-ext-enable ast \
    && docker-php-ext-install pcntl json \
    && docker-php-source delete

#
# Saseul
#
FROM php:7.3-fpm

RUN apt update \
    && apt autoclean \
    && rm -rf /var/lib/apt/lists/*

# ext
COPY --from=all-ext /usr/local/etc/php/conf.d/* /usr/local/etc/php/conf.d/
COPY --from=all-ext /usr/local/lib/php/extensions/no-debug-non-zts-20180731/* \
            /usr/local/lib/php/extensions/no-debug-non-zts-20180731/

# User settings
WORKDIR /var/saseul-origin

COPY . .

RUN groupadd saseul-node \
    && useradd -m -s /bin/bash -G saseul-node,www-data saseul \
    && chown -Rf saseul.saseul-node /var/saseul-origin

USER saseul:saseul-node

COPY --from=vendor /app/vendor .
