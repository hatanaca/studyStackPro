FROM php:8.4-cli-alpine

# Instalar extensões PHP
RUN docker-php-ext-install pdo 2>/dev/null || true

WORKDIR /sandbox

# Usuário não-root
RUN addgroup -S sandbox && adduser -S sandbox -G sandbox
USER sandbox

ENTRYPOINT ["php", "-r"]
