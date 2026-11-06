#!/bin/sh
# Renders the nginx config from the template at start-up, then runs nginx in the foreground.
set -eu

conf=/etc/nginx/conf.d/default.conf
envsubst '${LISTEN_PORT} ${TARGET_HOST} ${TARGET_PORT}' < "$conf.template" > "$conf"

# nginx exits at start-up if it cannot bind an IPv6 socket, so only keep the
# IPv6 listen line when the container has IPv6 (same check as the official
# image's 10-listen-on-ipv6-by-default.sh).
if ! grep -qs . /proc/net/if_inet6; then
    sed -i '/listen \[::\]/d' "$conf"
fi

exec nginx -g 'daemon off;'
