FROM php:8.2-cli

# ---------------------------------------------------------
# System dependencies
# ---------------------------------------------------------

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


# ---------------------------------------------------------
# PHP upload configuration
# ---------------------------------------------------------

RUN printf "upload_max_filesize=5M\npost_max_size=8M\n" \
    > /usr/local/etc/php/conf.d/uploads.ini


# ---------------------------------------------------------
# Composer
# ---------------------------------------------------------

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# ---------------------------------------------------------
# Application directory
# ---------------------------------------------------------

WORKDIR /app


# ---------------------------------------------------------
# Copy application
# ---------------------------------------------------------

COPY . .


# ---------------------------------------------------------
# PHP dependencies
# ---------------------------------------------------------

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction


# ---------------------------------------------------------
# Laravel public storage
# ---------------------------------------------------------

RUN php artisan storage:link


# ---------------------------------------------------------
# Frontend dependencies
# ---------------------------------------------------------

RUN npm ci


# ---------------------------------------------------------
# Reverb configuration for Vite build
# ---------------------------------------------------------

ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https

ENV VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
ENV VITE_REVERB_HOST=${VITE_REVERB_HOST}
ENV VITE_REVERB_PORT=${VITE_REVERB_PORT}
ENV VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}


# ---------------------------------------------------------
# Frontend production build
# ---------------------------------------------------------

RUN npm run build


# ---------------------------------------------------------
# Application port
# ---------------------------------------------------------

EXPOSE 10000


# ---------------------------------------------------------
# Start Laravel
# ---------------------------------------------------------

CMD php artisan serve \
    --host=0.0.0.0 \
    --port="${PORT:-10000}"