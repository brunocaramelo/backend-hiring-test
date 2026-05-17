FROM php:8.5-cli-alpine

RUN apk add --no-cache \
    bash \
    git \
    unzip

WORKDIR /app

COPY composer.json composer.lock* ./

COPY . .

CMD ["php", "-v"]