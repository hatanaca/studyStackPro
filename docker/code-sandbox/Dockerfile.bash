FROM alpine:3.19

RUN apk add --no-cache bash

WORKDIR /sandbox

RUN addgroup -S sandbox && adduser -S sandbox -G sandbox
USER sandbox

ENTRYPOINT ["bash", "-c"]
