FROM php:8.1-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libsodium-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli mbstring exif pcntl bcmath gd zip sodium

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Create system user to run Composer and Artisan Commands.
# WWW_UID/WWW_GID must match the host user owning the bind-mounted repo,
# or php-fpm cannot write vendor/ and storage/. Default 1000 keeps the
# historical behaviour; override via compose build args where the host
# uid differs (e.g. WWW_UID=1001 on ex63).
ARG WWW_UID=1000
ARG WWW_GID=1000
RUN groupadd -g ${WWW_GID} www
RUN useradd -u ${WWW_UID} -ms /bin/bash -g www www

# Copy existing application directory contents with proper ownership
COPY --chown=www:www . /var/www/html

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
