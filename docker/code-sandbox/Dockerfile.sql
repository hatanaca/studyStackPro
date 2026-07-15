FROM sqlite:alpine

WORKDIR /sandbox

RUN addgroup -S sandbox && adduser -S sandbox -G sandbox
USER sandbox

ENTRYPOINT ["sqlite3", ":memory:"]
