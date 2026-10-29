# Stage 1: Build frontend assets
FROM node:22-alpine AS npm-builder

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm ci && npm run build

# Stage 2: Nginx
FROM nginx:1.27.4-alpine3.21 AS nginx

ENV TARGET_HOST=127.0.0.1 \
    TARGET_PORT=9000 \
    LISTEN_PORT=80

COPY .docker/nginx/nginx_template_local.conf /etc/nginx/conf.d/default.conf.template

# Copy built assets from the frontend build stage
COPY --from=npm-builder /app/public /var/www/html/public

# Replace env var in runtime (not in buildtime)
CMD ["sh", "-c", "envsubst '${LISTEN_PORT} ${TARGET_HOST} ${TARGET_PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf && nginx -g 'daemon off;'"]
