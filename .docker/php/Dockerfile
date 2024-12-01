# Use PHP 8.2 FPM based on Debian Buster
FROM php:8.2-fpm-buster

# Set environment variable for the timezone
ARG TIMEZONE

# Copy custom PHP configuration (php.ini)
COPY php.ini /usr/local/etc/php/conf.d/docker-php-config.ini

# Install required dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    gnupg \
    g++ \
    procps \
    openssl \
    git \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libfreetype6-dev \
    libpng-dev \
    libjpeg-dev \
    libicu-dev \
    libonig-dev \
    libxslt1-dev \
    acl \
    && rm -rf /var/lib/apt/lists/*

# Configure GD extension (with JPEG and Freetype support)
RUN docker-php-ext-configure gd --with-jpeg --with-freetype

# Install necessary PHP extensions
RUN docker-php-ext-install \
    pdo pdo_mysql zip xsl gd intl opcache exif mbstring

# Set the timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && date

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/symfony

# Install Symfony binary (optional, if you want to use symfony CLI tool)
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Expose the port for PHP-FPM
EXPOSE 9000

# Define the entrypoint to run Composer install and Symfony commands
CMD ["sh", "-c", "composer install && php bin/console doctrine:migrations:migrate --no-interaction && php bin/console doctrine:fixtures:load --no-interaction && symfony serve"]
