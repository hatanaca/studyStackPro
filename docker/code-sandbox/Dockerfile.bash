FROM alpine:3.21

# Atualiza pacotes base para corrigir CVEs conhecidas
RUN apk upgrade --no-cache && apk add --no-cache bash

WORKDIR /sandbox

RUN addgroup -S sandbox && adduser -S sandbox -G sandbox
USER sandbox

ENTRYPOINT ["bash", "-c"]
