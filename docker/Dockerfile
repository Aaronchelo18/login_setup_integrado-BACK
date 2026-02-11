# Dockerfile "Todo en Uno" (VERSIÓN FINAL Y COMPLETA)

# ==============================================================================
# Etapa 1: Imagen Base y Dependencias
# ==============================================================================
FROM php:8.2-fpm-bullseye

WORKDIR /var/www/httpdocs
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor \
    git unzip zip curl \
    libpng-dev libonig-dev libxml2-dev libpq-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_pgsql

# ==============================================================================
# Etapa 2: Instalar Composer y Configurar el Sistema
# ==============================================================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN git config --system --add safe.directory /var/www/httpdocs

# ==============================================================================
# Etapa 3: Copiar Archivos de Configuración y Código
# ==============================================================================
# Copiar las configuraciones
COPY config/nginx/nginx.conf /etc/nginx/sites-enabled/default
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
# ¡NUEVO! Copiar nuestra configuración de pool de PHP-FPM.
# El prefijo 'zz-' asegura que se cargue al final, sobreescribiendo cualquier default.
COPY config/php/php-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Copiar el código de la aplicación.
COPY . .

# ==============================================================================
# Etapa 4: Permisos y Limpieza
# ==============================================================================
# ¡CORREGIDO! Asegurarse de que el directorio del socket exista.
RUN mkdir -p /run/php && \
    chown -R www-data:www-data /var/www/httpdocs && \
    chown -R www-data:www-data /run/php

# ==============================================================================
# Etapa 5: Ejecución
# ==============================================================================
EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]