FROM php:8.2-apache

# Install system dependencies and php extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilita módulos do Apache necessários (rewrite + proxy para /db → phpMyAdmin)
RUN a2enmod rewrite proxy proxy_http headers

# Helper to install composer (camada cacheável)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# OTIMIZAÇÃO: Copiar apenas composer.json primeiro para aproveitar cache
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN if [ -f composer.lock ]; then \
    composer install --no-interaction --optimize-autoloader --no-dev --no-scripts; \
    else \
    composer install --no-interaction --optimize-autoloader --no-scripts; \
    fi

# Quebra de Cache Forçada (Mude a data para forçar rebuild)
ARG CACHEBUST=2026-02-20_1
COPY . .

# Rodar scripts do composer após copiar tudo
RUN composer dump-autoload --optimize

# Setup permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache DocumentRoot e AllowOverride
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && sed -i '/\${APACHE_DOCUMENT_ROOT}/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Inclui a configuração de proxy para /db → phpMyAdmin
COPY apache-proxy.conf /etc/apache2/conf-available/proxy-phpmyadmin.conf
RUN a2enconf proxy-phpmyadmin

EXPOSE 80
