FROM nginx:1.27.4-alpine3.21

ENV PHP_HOST=php
ENV PHP_PORT=9000
ENV LISTEN_PORT=80
ENV HTML_ENDPOINT="/var/www/html/"

COPY .docker/nginx/nginx_template_local.conf /etc/nginx/conf.d/default.conf

WORKDIR ${HTML_ENDPOINT}
