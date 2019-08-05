#!/usr/bin/env bash

# set php.ini
phpenv config-add $TRAVIS_BUILD_DIR/conf/php.ini

# ed25519 extension
git clone https://github.com/encedo/php-ed25519-ext.git
cd php-ed25519-ext
phpize
./configure
make
make install

# Install extension
pecl install xdebug-2.7.2 memcached mongodb ast

echo '
extension=xdebug.so
extension=memcached.so
extension=mongodb.so
extension=ast.so
extension=ed25519.so
' >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini

