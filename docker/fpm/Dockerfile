FROM php:7.2-fpm
RUN    pecl install redis \
    && docker-php-ext-enable redis

FROM phpdockerio/php71-fpm:latest
# Install selected extensions and other stuff
RUN apt-get update

RUN apt-get -y install php7.2-mysql
RUN apt-get -y install php7.2-ctype
RUN apt-get -y install php7.2-dom
RUN apt-get -y install php7.2-bcmath
RUN apt-get -y install php7.2-bz2
RUN apt-get -y install php7.2-gmp
RUN apt-get -y install php-igbinary
RUN apt-get -y install php7.2-imap
RUN apt-get -y install php7.2-interbase
RUN apt-get -y install php7.2-intl
RUN apt-get -y install php7.2-odbc
RUN apt-get -y install php7.2-phpdbg
RUN apt-get -y install php7.2-soap
RUN apt-get -y install php-ssh2
RUN apt-get -y install php-tideways
RUN apt-get -y install php7.2-tidy
RUN apt-get -y install php7.2-xmlrpc
RUN apt-get -y install php7.2-xsl
RUN apt-get -y install php-yaml
RUN apt-get -y install php-zmq

RUN apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN chmod 777 /app/public/storage

