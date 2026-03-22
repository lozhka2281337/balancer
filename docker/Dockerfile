FROM php:8.4.19-fpm

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    g++ \
    git \
    libicu-dev \
    libzip-dev \
    postgresql-client \
    libpq-dev && \
    docker-php-ext-install intl opcache pdo pdo_pgsql && \
    pecl install apcu && \
    docker-php-ext-enable apcu && \
    docker-php-ext-configure zip && \
    docker-php-ext-install zip && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/project

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sS -o /usr/local/bin/symfony https://get.symfony.com/cli/symfony_linux_x86_64 && \
    chmod +x /usr/local/bin/symfony