FROM php:8.4-fpm

# Instala ferramentas essenciais do sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
    bash \
    curl \
    git \
    unzip \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP de forma oficial e otimizada via php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    mbstring \
    intl \
    zip \
    gd \
    opcache

# Usa o php.ini de produção como base
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Configurações de PHP (Uploads, Memória)
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/lotolab.ini \
    && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/lotolab.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/lotolab.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/lotolab.ini

# Copia configuração dedicada do OPcache e FPM
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia script de entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
