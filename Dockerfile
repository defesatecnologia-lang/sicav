FROM php:8.2-apache

LABEL maintainer="Sistema de Visitantes <dev@example.com>"
LABEL description="Sistema de Gerenciamento de Visitantes"

ARG ENVIRONMENT=production

RUN set -eux; \
    apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        unzip \
        git \
    && rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        gd \
        zip \
    && docker-php-ext-enable pdo_mysql gd zip

RUN set -eux; \
    a2enmod rewrite headers expires; \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

COPY . /var/www/html/

RUN set -eux; \
    mkdir -p /var/www/html/fotos_visitantes; \
    chown -R www-data:www-data /var/www/html/fotos_visitantes; \
    chmod 755 /var/www/html/fotos_visitantes

RUN set -eux; \
    if [ "$ENVIRONMENT" = "production" ]; then \
        rm -f /var/www/html/.env; \
        rm -rf /var/www/html/vendor /var/www/html/node_modules /var/www/html/db/*.sh; \
        find /var/www/html -name "*.md" -delete; \
    fi

COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
