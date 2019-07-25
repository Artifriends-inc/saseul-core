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
