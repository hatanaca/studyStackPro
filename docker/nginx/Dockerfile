# OpenResty mais recente (Alpine fat) para reduzir CVEs da cadeia base vs 1.25.x.
FROM openresty/openresty:1.27.1.2-0-alpine-fat

# Pacotes do SO (openssl, curl, expat, etc.) no momento do build — reduz CVEs vs. só a tag base.
RUN apk upgrade --no-cache

COPY nginx.conf /usr/local/openresty/nginx/conf/nginx.conf
COPY conf.d/studytrack.conf /etc/nginx/conf.d/default.conf
