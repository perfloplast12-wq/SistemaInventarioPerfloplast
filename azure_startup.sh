#!/bin/bash

echo "Configurando Nginx para Laravel..."

# 1. Cambiar la raíz de Nginx a la carpeta /public
# Azure por defecto apunta a /home/site/wwwroot, pero Laravel necesita /home/site/wwwroot/public
sed -i 's|root /home/site/wwwroot;|root /home/site/wwwroot/public;|g' /etc/nginx/sites-available/default

# 2. Reiniciar Nginx para aplicar el cambio
service nginx reload

echo "Ejecutando tareas de mantenimiento..."

# 3. Dar permisos a las carpetas de almacenamiento (Vital para que no de error 500)
chmod -R 775 /home/site/wwwroot/storage
chmod -R 775 /home/site/wwwroot/bootstrap/cache

# 4. Correr migraciones y limpiar caché
php /home/site/wwwroot/artisan migrate --force
php /home/site/wwwroot/artisan config:cache
php /home/site/wwwroot/artisan route:cache

echo "¡Sistema listo!"
