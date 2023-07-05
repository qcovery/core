FROM php:8.1.20RC1-fpm

# install additinal packages and PHP extensions
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        zlib1g-dev \
        libicu-dev \
        libc-client-dev \
        libkrb5-dev \
        libxml2-dev \
        g++ \
        git \
        zip \
        sudo \
        apt-transport-https \
        nano \
        nodejs \
        libxslt1.1 \
        libxslt1-dev \
    #&& docker-php-ext-install -j$(nproc) iconv mcrypt \
    && docker-php-ext-configure gd --with-jpeg=/usr/include/ --with-freetype=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl \
    #&& docker-php-ext-install -j$(nproc) mcrypt \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) imap \
    && docker-php-ext-install -j$(nproc) soap \
    && docker-php-ext-install -j$(nproc) xsl


RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN { \
        echo 'xdebug.mode=debug'; \
        echo 'xdebug.client_port=9000'; \
        echo 'xdebug.discover_client_host=0'; \
        echo 'xdebug.client_host=10.254.254.254'; \
        echo 'xdebug.idekey=PHPSTORM'; \
        echo 'xdebug.start_with_request=true'; \
        #echo 'xdebug.log=/var/www/html/logs/xdebug.log'; \
    } > /usr/local/etc/php/conf.d/xdebug.ini

# copy configurations
COPY conf/qcovery.ini /usr/local/etc/php/conf.d/
COPY conf/qcovery.pool.conf /usr/local/etc/php-fpm.d/

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
#RUN composer self-update --2

CMD ["php-fpm"]
