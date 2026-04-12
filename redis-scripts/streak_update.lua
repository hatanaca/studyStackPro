-- Script: streak_update
-- Propósito: atualizar streak diário de estudo sem round-trip múltiplo.
-- KEYS: KEYS[1] = chave do streak atual, KEYS[2] = chave do último dia estudado
-- ARGV: ARGV[1] = hoje (YYYY-MM-DD), ARGV[2] = ontem (YYYY-MM-DD)
-- Retorno: streak atual como inteiro
local streak_key   = KEYS[1]
local last_day_key = KEYS[2]
local today        = ARGV[1]
local yesterday    = ARGV[2]
local streak   = tonumber(redis.call('GET', streak_key)) or 0
local last_day = redis.call('GET', last_day_key)
if last_day == today then
  return streak
end
if last_day == yesterday then
  streak = streak + 1
else
  streak = 1
end
redis.call('SET', streak_key, streak, 'EX', 172800)
redis.call('SET', last_day_key, today, 'EX', 172800)
return streak
