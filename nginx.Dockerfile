FROM nginx:1.27.4-alpine3.21

ARG HTML_ENDPOINT="/var/www/html/"

COPY .docker/nginx/nginx_template_local.conf /etc/nginx/conf.d/default.conf

WORKDIR ${HTML_ENDPOINT}
