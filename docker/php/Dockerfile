FROM php:8.3.18RC1-fpm-alpine3.21

ENV RUN_DEPS \
    zlib \
    libzip \
    libpng \
    libjpeg-turbo \
    postgresql-libs

ENV BUILD_DEPS \
    zlib-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    postgresql-dev

ENV PHP_EXTENSIONS \
    opcache \
    zip \
    gd \
    bcmath \
    pgsql \
    pdo_pgsql

RUN apk add --no-cache --virtual .build-deps $BUILD_DEPS \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j "$(nproc)" $PHP_EXTENSIONS \
    && apk del .build-deps

RUN apk add --no-cache --virtual .run-deps $RUN_DEPS

RUN echo 'memory_limit = -1' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini; \
    echo 'session.save_path = /tmp' >> /usr/local/etc/php/conf.d/docker-php-session.ini; \
    echo 'session.gc_maxlifetime = 3600' >> /usr/local/etc/php/conf.d/docker-php-session.ini;

# E4: w produkcji nie pokazujemy stack trace / surowych błędów użytkownikowi –
# błędy nadal trafiają do logów (log_errors), tylko nie są wyświetlane w przeglądarce
RUN echo 'display_errors = Off' >> /usr/local/etc/php/conf.d/docker-php-error-reporting.ini; \
    echo 'display_startup_errors = Off' >> /usr/local/etc/php/conf.d/docker-php-error-reporting.ini; \
    echo 'html_errors = Off' >> /usr/local/etc/php/conf.d/docker-php-error-reporting.ini; \
    echo 'log_errors = On' >> /usr/local/etc/php/conf.d/docker-php-error-reporting.ini; \
    echo 'error_log = /tmp/php_errors.log' >> /usr/local/etc/php/conf.d/docker-php-error-reporting.ini;

# Copy the application code
COPY . /app

VOLUME ["/app"]