#!/bin/bash

set -e

echo "Starting application..."

# Ejecutar migraciones (opcional - descomenta si quieres que se ejecuten automáticamente)
# php artisan migrate --force

# Crear enlace simbólico de storage si no existe
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link
fi

# Iniciar supervisor que manejará PHP-FPM y Nginx
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
