FROM php:8-alpine

ARG UNAME=david
ARG UGROUP=david
ARG GID=1000
ARG UID=1000

RUN addgroup -g $GID $UGROUP
RUN adduser \
    --disabled-password \
    --gecos "" \
    --home "/home/$UNAME" \
    --ingroup "$UGROUP" \
    --no-create-home \
    --uid "$UID" \
    "$UNAME"
RUN addgroup $UNAME $UGROUP
RUN addgroup $UNAME root
RUN mkdir /home/$UNAME
RUN chown $UNAME:$UGROUP /home/$UNAME

RUN apk update && apk upgrade && \
    apk add --no-cache make mysql-client git

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_mysql opcache @composer

WORKDIR /app