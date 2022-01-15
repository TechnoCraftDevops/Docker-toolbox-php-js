
FROM jakzal/phpqa:php7.4-alpine

# update alpine
RUN apk update && apk upgrade

# install linux tools
RUN apk add openssh-client wget curl git make bash libzip-dev zip ncurses

# Copy the docker client from local docker image
COPY --from=docker /usr/local/bin/docker /usr/bin/docker

# install php & composer
RUN php -v
RUN composer -V

# install symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash
RUN mv /root/.symfony/bin/symfony /usr/local/bin/symfony

# install php dependency
RUN apk add php7-curl \
            php7-openssl \
            php7-iconv \
            php7-json \
            php7-mbstring \
            php7-phar \
            php7-dom --repository http://nl.alpinelinux.org/alpine/edge/testing/ && rm /var/cache/apk/*
RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} autoconf \ 
  && pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && apk del pcre-dev ${PHPIZE_DEPS}
RUN echo xdebug.mode=coverage > /usr/local/etc/php/conf.d/xdebug.ini
# install release tool
RUN composer global require marcocesarato/php-conventional-changelog
RUN echo -e '#!/usr/bin/env bash' >> ./release-it-first
RUN echo "php ~/.composer/vendor/marcocesarato/php-conventional-changelog/conventional-changelog --first-release" >> ./release-it-first
RUN echo -e '#!/usr/bin/env bash' >> ./release-it
RUN echo "php ~/.composer/vendor/marcocesarato/php-conventional-changelog/conventional-changelog --commit" >> ./release-it
RUN chmod +x release-it-first
RUN chmod +x release-it
RUN cp ./release-it-first /usr/local/bin/
RUN cp ./release-it /usr/local/bin/
RUN rm ./release-it-first
RUN rm ./release-it

# install commit linter
RUN git clone https://github.com/TechnoCraftDevops/ci-conventionnal-commit-linter.git
RUN cp ./ci-conventionnal-commit-linter/ci-commit-linter /usr/local/bin/

#install unsed tools
RUN composer global require icanhazstring/composer-unused
RUN php --ini
RUN php --ri xdebug | grep coverage
CMD [ "bash" ]