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
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

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

# Install frontend dependencies.
RUN npm install

# Build Vite production assets.
RUN npm run build

# Document the port used by the application.
EXPOSE 10000

# Run migrations and start Laravel.
CMD php artisan migrate --force && \
    php artisan serve \
        --host=0.0.0.0 \
        --port="${PORT:-10000}"