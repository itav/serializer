FROM php:7.1-cli

WORKDIR /app

RUN apt-get update \
        && apt-get install -y \
             zlib1g-dev \
            git \
        && docker-php-ext-install -j$(nproc) pdo pdo_mysql bcmath zip pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /app

CMD ["tail","-f", "/etc/hosts"]
