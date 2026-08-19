#!/bin/bash
set -e

# .env é opcional em produção: o Docker Compose injeta as variáveis
# diretamente via environment. Em filesystems read_only (hardening),
# apenas usa o .env se ele já existir e for legível.
if [ -f /var/www/html/.env ] && [ -r /var/www/html/.env ]; then
    echo "--> Sourcing /var/www/html/.env..."
    while IFS='=' read -r key val || [ -n "$key" ]; do
        [[ "$key" =~ ^#.*$ || -z "$key" ]] && continue
        if [ -z "${!key+x}" ]; then
            export "$key=$val"
        fi
    done < /var/www/html/.env
fi

# Corrige permissões apenas quando roda como root (dev/overrides antigos).
# Em produção o container roda como app e o volume já é gravável.
if [ "$(id -u)" = "0" ]; then
    echo "--> Setting permissions..."
    chown -R app:app /var/www/html/fotos_visitantes 2>/dev/null || true
    chmod 755 /var/www/html/fotos_visitantes 2>/dev/null || true
fi

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
