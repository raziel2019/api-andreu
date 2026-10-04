FROM php:8.5-fpm

ARG PHP_MEMORY_LIMIT=1024M
ARG LARAVEL_VERSION=^13.0

ENV LARAVEL_VERSION=${LARAVEL_VERSION}

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libnss3 \
    libatk-bridge2.0-0 \
    libgtk-3-0 \
    libxss1 \
    libasound2 \
    libgbm-dev \
    libx11-xcb1 \
    libxcb-dri3-0 \
    libdrm2 \
    libxdamage1 \
    libxrandr2 \
    libxcomposite1 \
    libxcursor1 \
    libxi6 \
    libxtst6 \
    git \
    curl \
    zip \
    unzip \
    gnupg \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libmagickwand-dev \
    fontconfig \
    fonts-dejavu \
    fonts-liberation \
    xfonts-75dpi \
    xfonts-base \
    && rm -rf /var/lib/apt/lists/*

# Configurar GD correctamente
RUN docker-php-ext-configure gd \
    --with-freetype=/usr/include/ \
    --with-jpeg=/usr/include/

# Instalar extensiones PHP necesarias para Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Instalar extensiones PECL
RUN pecl install imagick redis \
    && docker-php-ext-enable imagick redis

# Configurar limite de memoria PHP
RUN echo "memory_limit=${PHP_MEMORY_LIMIT}" > /usr/local/etc/php/conf.d/memory-limit.ini

RUN printf "upload_max_filesize=5M\npost_max_size=12M\n" > /usr/local/etc/php/conf.d/uploads.ini

# Instalar Node (igual que servidores típicos Plesk)
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y nodejs

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear Laravel automaticamente en el volumen si el proyecto aun no existe
COPY docker/app/entrypoint.sh /usr/local/bin/laravel-entrypoint
RUN chmod +x /usr/local/bin/laravel-entrypoint

WORKDIR /var/www

ENTRYPOINT ["laravel-entrypoint"]
CMD ["php-fpm"]
