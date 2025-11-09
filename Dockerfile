FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (including PostgreSQL)
RUN docker-php-ext-install pdo_mysql pdo_pgsql mysqli mbstring exif pcntl bcmath gd

# Enable Apache modules
RUN a2enmod rewrite headers

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Install dependencies
RUN composer install --no-dev --optimize-autoloader || true

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache for Render
RUN sed -i 's/Listen 80/Listen ${PORT:-10000}/' /etc/apache2/ports.conf \
    && sed -i 's/:80/:${PORT:-10000}/' /etc/apache2/sites-available/000-default.conf

# Expose port
EXPOSE ${PORT:-10000}

# Start Apache
CMD ["apache2-foreground"]
