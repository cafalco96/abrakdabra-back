# Usa PHP 8.2 con FPM
FROM php:8.2-fpm

# Instalar dependencias del sistema y extensiones PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Limpiar caché
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de dependencias primero para aprovechar caché de Docker
COPY composer.json composer.lock ./

# Instalar dependencias de PHP
RUN composer install --no-dev --no-scripts --optimize-autoloader --prefer-dist

# Copiar el resto de la aplicación
COPY . .

# Copiar configuración de Nginx
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# Copiar configuración de Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Optimizar autoloader (sin config:cache ni route:cache - se hacen en start.sh con env vars disponibles)
RUN composer dump-autoload --optimize

# Crear directorios necesarios de Laravel y configurar permisos
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Crear script de inicio
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Exponer puerto (Render asignará el puerto dinámicamente)
EXPOSE 8080

# Comando de inicio
CMD ["/usr/local/bin/start.sh"]
