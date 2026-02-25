#!/bin/bash

set -e

echo "Starting application..."

# Cachear configuración con las variables de entorno disponibles en runtime
php artisan config:cache
php artisan route:cache

# Ejecutar migraciones
php artisan migrate --force

# Crear enlace simbólico de storage si no existe
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link
fi

# Iniciar supervisor que manejará PHP-FPM y Nginx
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
