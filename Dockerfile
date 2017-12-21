FROM php:7.1-cli-alpine

RUN docker-php-ext-install -j$(cat /proc/cpuinfo | grep processor | wc -l) bcmath \
        && apk --no-cache add wget git autoconf cmake build-base openssl-dev \
        && git clone git://github.com/alanxz/rabbitmq-c.git -b master /usr/src/rabbitmq-c \
        && ls -l /usr/src/ \
        && cd /usr/src/rabbitmq-c \
        && git submodule init \
        && git submodule update \
        && mkdir build \
        && cd build \
        && cmake -DCMAKE_INSTALL_PREFIX=/usr -DCMAKE_INSTALL_LIBDIR=lib .. \
        && cmake --build /usr/src/rabbitmq-c/build --config Release  \
        && make install \
        && pecl install amqp \
        && docker-php-ext-enable amqp \
        && apk del cmake build-base autoconf

ADD . /usr/share/symfony-app
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && if [ $(wget -q -O - https://composer.github.io/installer.sig) != $(php -r "echo hash_file('SHA384', 'composer-setup.php');") ]; \
       then \
           >&2 echo 'ERROR: Invalid installer signature'; \
           rm composer-setup.php; \
           exit 1; \
       fi \
    && php composer-setup.php --install-dir=/usr/local/bin \
    && php -r "unlink('composer-setup.php');" \
    && chmod +x /usr/local/bin/composer.phar \
    && cd /usr/share/symfony-app \
    && composer.phar install

VOLUME /usr/share/symfony-app