FROM postgres:16 AS builder

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    ca-certificates \
    git \
    lua5.4 \
    lua5.4-dev \
    postgresql-server-dev-16 \
 && git clone --depth 1 https://github.com/RhodiumToad/pllua-ng.git /tmp/pllua \
 && cd /tmp/pllua \
 && make \
    PG_CONFIG="$(command -v pg_config)" \
    LUA=lua5.4 \
    LUAC=luac5.4 \
    LUA_INCDIR=/usr/include/lua5.4 \
    LUALIB="-llua5.4" \
 && make install \
    PG_CONFIG="$(command -v pg_config)" \
    LUA=lua5.4 \
    LUAC=luac5.4 \
    LUA_INCDIR=/usr/include/lua5.4 \
    LUALIB="-llua5.4" \
 && rm -rf /tmp/pllua /var/lib/apt/lists/*

FROM postgres:16

RUN apt-get update && apt-get install -y --no-install-recommends \
    lua5.4 \
 && rm -rf /var/lib/apt/lists/*

COPY --from=builder /usr/lib/postgresql/16/lib/pllua.so /usr/lib/postgresql/16/lib/pllua.so
COPY --from=builder /usr/share/postgresql/16/extension/pllua* /usr/share/postgresql/16/extension/
