#!/bin/sh

echo "Starting Pak-All-Tests Azure container initialization..."

# 1. Apply Nginx configuration & PHP uploads.ini (Azure App Service container quirk)
if [ -f /home/site/wwwroot/default ]; then
    cp /home/site/wwwroot/default /etc/nginx/sites-available/default
fi

if [ -f /home/site/wwwroot/uploads.ini ]; then
    cp /home/site/wwwroot/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
fi

# Reload Nginx and PHP-FPM to apply upload limits and FastCGI buffers
service nginx reload 2>/dev/null || true
(kill -USR2 $(pidof php-fpm | awk '{print $1}') 2>/dev/null || true)

# 2. Verify storage symlink
php /home/site/wwwroot/artisan storage:link 2>/dev/null || true

# 3. Clear and warm up application caches
php /home/site/wwwroot/artisan config:cache 2>/dev/null || true
php /home/site/wwwroot/artisan route:cache 2>/dev/null || true
php /home/site/wwwroot/artisan view:cache 2>/dev/null || true

# 4. Start background queue worker daemon
# Processes bulk question imports, knowledge vectorization, and background tasks
echo "Launching background queue worker daemon..."
nohup php /home/site/wwwroot/artisan queue:work --tries=3 --timeout=180 --sleep=3 --rest=1 >> /home/site/wwwroot/storage/logs/queue.log 2>&1 &

echo "Azure container initialization complete. Web server ready."
