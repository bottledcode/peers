FROM php:8.2-zts AS build

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions @composer ev sodium zip intl uuid ev pcntl parallel apcu

COPY composer.json composer.lock /app/
WORKDIR /app

RUN composer install --no-dev -o --apcu-autoloader

FROM build AS css
ADD --chmod=777 https://github.com/tailwindlabs/tailwindcss/releases/download/v3.3.2/tailwindcss-linux-x64 /usr/local/bin/twcss
COPY . .
RUN twcss -i public/assets/style.css -o public/assets/web.css --minify

FROM registry.bottled.codes/base/frankenphp:latest AS web

RUN install-php-extensions @composer dom intl mbstring sodium zip uuid apcu
RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini && \
	echo "opcache.jit_buffer_size=100M" >> $PHP_INI_DIR/php.ini

COPY . /app/
COPY --from=css /app/public/assets/web.css /app/public/assets/web.css
COPY --from=build /app/vendor /app/vendor

RUN composer dump -o --apcu

FROM build AS backend

RUN apt update && apt install -y inotify-tools && \
    rm -rf /var/lib/apt/lists/*
RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini && \
    echo "apc.enable_cli=1" >> $PHP_INI_DIR/php.ini && \
    echo "apc.enable=1" >> $PHP_INI_DIR/php.ini


COPY --from=css /usr/local/bin/twcss /usr/local/bin/twcss
COPY . /app/

ENTRYPOINT ["php","src/Server.php"]
