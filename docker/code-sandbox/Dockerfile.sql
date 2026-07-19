FROM alpine/sqlite

# Atualiza pacotes base para corrigir CVEs
RUN apk upgrade --no-cache

WORKDIR /sandbox

RUN addgroup -S sandbox && adduser -S sandbox -G sandbox
USER sandbox

ENTRYPOINT ["sqlite3", ":memory:"]
