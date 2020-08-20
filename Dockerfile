FROM toroia/phalcon-cli

RUN apk update \
  && apk add --no-cache \
  -X http://dl-cdn.alpinelinux.org/alpine/edge/testing \
  php7-pecl-mongodb
