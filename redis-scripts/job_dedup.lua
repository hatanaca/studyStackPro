-- Script: job_dedup
-- Propósito: evitar despacho duplicado de jobs por uma janela curta.
-- KEYS: KEYS[1] = chave de lock do job
-- ARGV: ARGV[1] = TTL do lock em segundos
-- Retorno: 1 se o lock foi criado, 0 se já existia
local key = KEYS[1]
local ttl = tonumber(ARGV[1])
local result = redis.call('SET', key, '1', 'NX', 'EX', ttl)
if result then
  return 1
end
return 0
