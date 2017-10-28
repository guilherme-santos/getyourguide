FROM php:7.1-cli-alpine3.4

MAINTAINER Guilherme Silveira <xguiga@gmail.com>

# Installing composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --install-dir /usr/local/bin --filename=composer && \
    php -r "unlink('composer-setup.php');"

WORKDIR /usr/share/getyourguide

# Improving cache layer...
# First copy just composer.{json,lock} because if we change some file in our project
# we don't need to install all dependencies again, only if we change composer files related.
COPY composer.json composer.lock ./
RUN composer install

# Now we can copy all files
COPY . ./

ENTRYPOINT ["php", "./available-products.php"]

CMD ["--help"]
