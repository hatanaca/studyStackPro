FROM php:8.4-cli-alpine

# Atualiza pacotes base Alpine para corrigir CVEs
RUN apk upgrade --no-cache

WORKDIR /sandbox

# Usuário não-root
RUN addgroup -S sandbox && adduser -S sandbox -G sandbox
USER sandbox

ENTRYPOINT ["php", "-r"]
