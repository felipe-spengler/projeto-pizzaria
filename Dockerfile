FROM php:8.2-apache

# Install system dependencies and php extensions
# Esta camada só será reconstruída se mudarmos as dependências do sistema
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

# Helper to install composer (camada cacheável)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# OTIMIZAÇÃO: Copiar apenas composer.json primeiro para aproveitar cache
# Se composer.json não mudou, esta camada será reutilizada do cache
COPY composer.json composer.lock* ./

# Install PHP dependencies (só roda se composer.json mudou)
# Usa --no-dev para produção e --no-scripts para acelerar
RUN if [ -f composer.lock ]; then \
    composer install --no-interaction --optimize-autoloader --no-dev --no-scripts; \
    else \
    composer install --no-interaction --optimize-autoloader --no-scripts; \
    fi

# Copiar resto dos arquivos (só esta parte será reconstruída quando código mudar)
COPY . .

# Rodar scripts do composer após copiar tudo
RUN composer dump-autoload --optimize

# Setup permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache DocumentRoot and AllowOverride
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && sed -i '/<Directory \${APACHE_DOCUMENT_ROOT}>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
