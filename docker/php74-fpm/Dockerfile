FROM php:7.4-fpm

RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libcurl4-gnutls-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libmemcached-dev \
    libonig-dev

RUN docker-php-ext-install pdo pdo_mysql opcache intl exif soap
RUN apt-get update -y && apt-get install -y \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libpng-dev libxpm-dev \
    libfreetype6-dev \
    supervisor

RUN apt-get update && \
    apt-get install -y zlib1g-dev unzip

RUN docker-php-ext-install mbstring
RUN apt-get install -y libzip-dev
RUN docker-php-ext-install zip xml

RUN docker-php-ext-configure gd --with-jpeg=/usr/include/ --with-freetype=/usr/include/
RUN docker-php-ext-install gd
#RUN docker-php-ext-install -j$(nproc) gd curl pdo_mysql opcache xml zip mbstring intl

RUN pecl install -f memcached && \
    docker-php-ext-enable memcached

RUN echo ipv4 >> ~/.curlrc && \
    curl -v -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    echo "Done"

COPY ./etc /usr/local/etc