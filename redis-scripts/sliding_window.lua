-- Script: sliding_window
-- Propósito: aplicar rate limit por janela deslizante com retry_after.
-- KEYS: KEYS[1] = chave do bucket de rate limit
-- ARGV: ARGV[1] = timestamp atual em ms, ARGV[2] = janela em ms, ARGV[3] = limite
-- Retorno: {1, 0} se permitido; {0, retry_after} se bloqueado
local key    = KEYS[1]
local now    = tonumber(ARGV[1])
local window = tonumber(ARGV[2])
local limit  = tonumber(ARGV[3])
redis.call('ZREMRANGEBYSCORE', key, 0, now - window)
local count = redis.call('ZCARD', key)
if count >= limit then
  local oldest      = redis.call('ZRANGE', key, 0, 0, 'WITHSCORES')
  local retry_after = math.ceil((tonumber(oldest[2]) + window - now) / 1000)
  return {0, retry_after}
end
redis.call('ZADD', key, now, now .. '-' .. math.random(999999))
redis.call('PEXPIRE', key, window)
return {1, 0}
