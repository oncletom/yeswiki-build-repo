FROM php:7.3-cli-alpine

RUN apk add composer git jq zip

COPY composer.lock /composer.lock
RUN cp /composer.lock /composer.json && composer install

COPY composer.json /composer.json
COPY entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
