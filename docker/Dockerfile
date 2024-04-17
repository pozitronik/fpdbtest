FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
        && pecl install xdebug-3.3.2 \
        && docker-php-ext-enable xdebug

ADD ./php/php.ini /usr/local/etc/php/php.ini


RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer
RUN curl --remote-name --time-cond ./php/cacert.pem https://curl.se/ca/cacert.pem

ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /app

COPY . /app

