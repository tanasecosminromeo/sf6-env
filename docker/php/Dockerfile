FROM php:8.4.12-fpm-alpine3.21
LABEL maintainer="Cosmin-Romeo TANASE <cosmin@tanase.dev>"

# Install Symfony 3 requirements
RUN set -xe \
    # Update repository
    && apk update \
    && apk upgrade \
    # Fixed Intl version
    && apk add --no-cache libintl icu icu-dev shadow sudo \
    && docker-php-ext-install intl \
    && apk del icu-dev \
    # Install XML and DOM extensions
    && apk add --no-cache libxml2-dev \
    && docker-php-ext-install dom xml \
    && docker-php-ext-install opcache \
    # PDO
    && docker-php-ext-install pdo \
    # PDO MySQL
    && docker-php-ext-install pdo_mysql \
    && apk add --update bash \
    # Add mariadb client
    && apk add --no-cache mariadb-client \
    # Install Supervisor
    && apk add --no-cache supervisor \
    && adduser -D app

# Clear
RUN rm -rf /tmp/* /var/cache/apk/*
# Set the WORKDIR to /code
WORKDIR /code

#RUN pecl install xdebug
#RUN docker-php-ext-enable xdebug
#COPY ./.docker/php/xdebug.ini $PHP_INI_DIR/conf.d/xdebug.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer 

RUN mkdir -p /home/app/.composer/cache && chmod 0777 /home/app/.composer/cache

RUN echo post_max_size=60M >> /usr/local/etc/php/conf.d/app.ini \
    && echo upload_max_filesize=60M >> /usr/local/etc/php/conf.d/app.ini \
    && echo 'expose_php = off' >> /usr/local/etc/php/conf.d/app.ini

# Set up Supervisor for Symfony Messenger
RUN mkdir -p /var/log/supervisor
COPY supervisord.conf /etc/supervisor.d/messenger.ini
COPY supervisor.sh /usr/local/bin/sup
RUN chmod +x /usr/local/bin/sup

# Set up the owner and run php-fpm

ADD app.sh /usr/bin/app
RUN chmod +x /usr/bin/app

ADD entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
CMD ["/entrypoint.sh"]
