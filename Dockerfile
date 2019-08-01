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
# ed25519
#
FROM php:7.3-cli AS ext-ed25519

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
    && docker-php-ext-enable ed25519

#
# Saseul
#
FROM php:7.3-fpm

RUN apt update \
    && apt install -y --no-install-recommends \
            build-essential git libmemcached-dev zlib1g-dev libssl-dev \
    && apt autoclean \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug 2.7.0 \
    && docker-php-ext-enable xdebug \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && pecl install ast \
    && docker-php-ext-enable ast \
    && rm -rf /tmp/pear/*

RUN docker-php-ext-install pcntl json


# User settings
WORKDIR /var/saseul-origin

COPY . .

RUN groupadd saseul-node \
    && useradd -s /bin/bash -G saseul-node,www-data saseul \
    && chown -Rf saseul.saseul-node /var/saseul-origin

USER saseul:saseul-node

COPY --from=vendor /app/vendor .

# ext
COPY --from=ext-ed25519 /usr/local/etc/php/conf.d/* /usr/local/etc/php/conf.d/
COPY --from=ext-ed25519 /usr/local/lib/php/extensions/no-debug-non-zts-20180731/* /usr/local/lib/php/extensions/no-debug-non-zts-20180731/
