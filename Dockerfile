
FROM jakzal/phpqa:php7.4-alpine

# update alpine LINUX
RUN apk update && apk upgrade

# install linux tools LINUX
RUN apk add openssh-client wget curl git make bash libzip-dev zip ncurses nodejs npm

# Copy the docker client from local docker image
COPY --from=docker /usr/local/bin/docker /usr/bin/docker

# install php & composer PHP
RUN php -v
RUN composer -V

# install symfony CLI PHP
RUN apk add --no-cache bash
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash
RUN apk add symfony-cli
RUN symfony


# install php dependency PHP
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

# install release tool PHP
RUN composer global require marcocesarato/php-conventional-changelog
RUN echo -e '#!/usr/bin/env bash' >> ./release-it-first
RUN echo "php /tools/.composer/vendor/marcocesarato/php-conventional-changelog/conventional-changelog --first-release" >> ./release-it-first
RUN echo -e '#!/usr/bin/env bash' >> ./release-it
RUN echo "php /tools/.composer/vendor/marcocesarato/php-conventional-changelog/conventional-changelog --commit" >> ./release-it
RUN chmod +x release-it-first
RUN chmod +x release-it


# install commit linter NODE
RUN npm install --g @commitlint/prompt-cli @commitlint/cli @commitlint/config-conventional conventional-changelog-angular

RUN php --ini
RUN php --ri xdebug | grep coverage
CMD [ "bash" ]