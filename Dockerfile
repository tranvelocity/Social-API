FROM php:8.1-fpm-buster


# Use Debian buster archive sources
RUN sed -i 's|http://deb.debian.org/debian|http://archive.debian.org/debian|g' /etc/apt/sources.list \
    && sed -i 's|http://security.debian.org/debian-security|http://archive.debian.org/debian-security|g' /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y git zip unzip

RUN docker-php-ext-install bcmath pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

EXPOSE 9000
