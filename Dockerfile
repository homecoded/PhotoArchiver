FROM ubuntu:latest

RUN apt-get update
RUN apt-get -y upgrade

# Install requirements
RUN DEBIAN_FRONTEND=noninteractive apt-get -y install \
    apache2 \
    php \
    php-gd \
    libapache2-mod-php \
    exiftool

# Enable apache mods.
RUN a2enmod rewrite

# Manually set up the apache environment variables
#ENV APACHE_RUN_USER ubuntu
#ENV APACHE_RUN_GROUP ubuntu
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

EXPOSE 80

RUN a2enmod deflate
RUN a2enmod headers
RUN /etc/init.d/apache2 restart

# Update the default apache site with the config we created.
ADD build/apache-config.conf /etc/apache2/sites-enabled/000-default.conf
ADD build/envvars /etc/apache2/envvars
ADD build/php.ini /etc/php/8.3/apache2/conf.d/99-custom.ini

# By default, simply start apache.
CMD /usr/sbin/apache2ctl -D FOREGROUND
