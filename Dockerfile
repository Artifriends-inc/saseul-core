FROM php:7.3-fpm-alpine

RUN apk update --no-cache \
    && apk upgrade --no-cache \
    && apk add --no-cache shadow \
    && apk add --no-cache --virtual .build-deps \
           autoconf file g++ gcc libc-dev make pkgconf re2c git openssl-dev bash \
    && apk add --no-cache --update --virtual .memcached-deps \
           zlib-dev libmemcached-dev cyrus-sasl-dev \
    && docker-php-source extract \
    && rm -rf /var/cache/apk && mkdir -p /var/cache/apk

# php-ext
RUN pecl install xdebug 2.7.0 \
    && docker-php-ext-enable xdebug \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && pecl install ast \
    && docker-php-ext-enable ast \
    && rm -rf /tmp/pear/*

# ed25519
WORKDIR /tmp
RUN git clone https://github.com/encedo/php-ed25519-ext.git \
    && cd php-ed25519-ext \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && make test \
    && docker-php-ext-enable ed25519 \
    && cd / \
    && rm -rf /tmp/php-ed25519-ext

# composer
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/local/bin --filename=composer \
    && composer global require hirak/prestissimo --no-plugins --no-scripts

WORKDIR /var/saseul-origin

COPY . .

#RUN useradd -s /bin/bash saseul \
#    && mkdir /home/saseul && chown -R saseul /home/saseul \
#    && chown saseul.saseul -R /var/saseul-origin
#
#USER saseul:saseul
#
#RUN cd api && composer install --no-dev && composer dump-autoload -o && composer clear-cache \
#    && cd ../saseuld && composer install --no-dev && composer dump-autoload -o && composer clear-cache \
#    && cd ../script && composer install --no-dev && composer dump-autoload -o && composer clear-cache
