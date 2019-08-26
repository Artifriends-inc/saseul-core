#!/usr/bin/env bash

# set php.ini
phpenv config-add $TRAVIS_BUILD_DIR/conf/php.ini

# Install extension
pecl install ast xdebug memcached mongodb

# ed25519 extension
git clone https://github.com/encedo/php-ed25519-ext.git
cd php-ed25519-ext
phpize
./configure
make
make install
cp ./modules/ed25519.so  ~/.phpenv/versions/$(phpenv version-name)/lib/php/extensions/no-debug-zts-20180731/
echo "
extension = ed25519.so
extension = mongodb.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
