FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    postgresql-client \
    unzip \
    && docker-php-ext-install \
        intl \
        opcache \
        pdo \
        pdo_pgsql \
        zip \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/project

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony