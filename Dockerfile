FROM php:8.2-cli

# Install system packages required by Laravel and the frontend build.
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    zip \
    nodejs \
    npm \
    && docker-php-ext-install \
        pcntl \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP upload limits for agency images.
RUN printf "upload_max_filesize=5M\npost_max_size=8M\n" \
    > /usr/local/etc/php/conf.d/uploads.ini

# Copy Composer from the official Composer image.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory inside the container.
WORKDIR /app

# Copy the Laravel project into the container.
COPY . .

# Install production PHP dependencies.
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Create Laravel's public storage symlink.
RUN php artisan storage:link

# ---------------------------------------------------------
# Production Reverb configuration for the frontend build.
# ---------------------------------------------------------
#
# These values are intentionally public because Vite places
# VITE_* values inside the browser JavaScript bundle.
#
# Never place REVERB_APP_SECRET or another private secret here.
#

ENV VITE_REVERB_APP_KEY=knowurlocal-key
ENV VITE_REVERB_HOST=knowurlocal-reverb-production.up.railway.app
ENV VITE_REVERB_PORT=443
ENV VITE_REVERB_SCHEME=https

# Install frontend dependencies.
RUN npm install

# Build Vite production assets using the public Reverb values.
RUN npm run build

# Document the port used by the application.
EXPOSE 10000

# Run migrations and start Laravel.
CMD php artisan migrate --force && \
    php artisan serve \
        --host=0.0.0.0 \
        --port="${PORT:-10000}"