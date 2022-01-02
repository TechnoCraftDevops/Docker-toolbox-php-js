
FROM jakzal/phpqa:php7.4-alpine

# update alpine
RUN apk update && apk upgrade

# install tools
RUN apk add openssh-client wget curl git make bash libzip-dev zip

# Copy the docker client from local docker image
COPY --from=docker /usr/local/bin/docker /usr/bin/docker

# install php & composer
RUN php -v
RUN composer -V

# install symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash

# install php dependency
RUN apk add php7-curl \
            php7-openssl \
            php7-iconv \
            php7-json \
            php7-mbstring \
            php7-phar \
            php7-mysqli \
            php7-pdo \
            php7-pdo_mysql \
            php7-xdebug \
            php7-dom --repository http://nl.alpinelinux.org/alpine/edge/testing/ && rm /var/cache/apk/*

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

CMD [ "bash" ]