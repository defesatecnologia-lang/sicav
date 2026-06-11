#!/bin/bash
set -e

if [ ! -f /var/www/html/.env ] && [ -f /var/www/html/.env.example ]; then
    echo "--> Creating .env from .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Source .env only for variables not already set (docker-compose env vars take precedence)
if [ -f /var/www/html/.env ]; then
    while IFS='=' read -r key val || [ -n "$key" ]; do
        [[ "$key" =~ ^#.*$ || -z "$key" ]] && continue
        if [ -z "${!key+x}" ]; then
            export "$key=$val"
        fi
    done < /var/www/html/.env
fi

echo "--> Setting permissions..."
chown -R www-data:www-data /var/www/html/fotos_visitantes 2>/dev/null || true
chmod 755 /var/www/html/fotos_visitantes 2>/dev/null || true

echo "--> Waiting for MySQL..."
until php -r "
    try {
        new PDO('mysql:host=${VISITORPASS_DB_HOST:-db};port=${VISITORPASS_DB_PORT:-3306}', '${VISITORPASS_DB_USER:-vpuser}', '${VISITORPASS_DB_PASS:-visitorpassword}', [PDO::ATTR_TIMEOUT => 3]);
        echo 'ok';
    } catch (PDOException \$e) {
        echo \$e->getMessage();
        exit(1);
    }
" 2>/dev/null | grep -q 'ok'; do
    sleep 2
done

echo "--> MySQL is ready!"

echo "--> Running database migration..."
php /var/www/html/docker/migrate.php || echo "WARNING: Migration may have failed, check database."

echo "--> Starting Apache..."
exec "$@"
