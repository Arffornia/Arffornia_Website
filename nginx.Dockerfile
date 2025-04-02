FROM nginx:1.27.4-alpine3.21

ENV TARGET_HOST=app \
    TARGET_PORT=9000 \
    LISTEN_PORT=80 \
    HTML_ENDPOINT="/var/www/html/"

COPY .docker/nginx/nginx_template_local.conf /etc/nginx/conf.d/default.conf.template

WORKDIR ${HTML_ENDPOINT}

# Replace env var in runtime (not in buildtime)
CMD ["sh", "-c", "envsubst '${LISTEN_PORT} ${TARGET_HOST} ${TARGET_PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf && nginx -g 'daemon off;'"]
