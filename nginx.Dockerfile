FROM nginx:1.27.4-alpine3.21 AS nginx

ENV TARGET_HOST=127.0.0.1 \
    TARGET_PORT=9000 \
    LISTEN_PORT=80

COPY .docker/nginx/nginx_template_local.conf /etc/nginx/conf.d/default.conf.template

COPY --from=ghcr.io/arffornia/arffornia_website:latest /var/www/html/public /var/www/html/public

# Replace env var in runtime (not in buildtime)
CMD ["sh", "-c", "envsubst '${LISTEN_PORT} ${TARGET_HOST} ${TARGET_PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf && nginx -g 'daemon off;'"]
