-- Extensões necessárias para o StudyTrack Pro
CREATE EXTENSION IF NOT EXISTS "pgcrypto";   -- gen_random_uuid()
CREATE EXTENSION IF NOT EXISTS "pg_trgm";    -- busca por trigrama (tecnologias)
CREATE EXTENSION IF NOT EXISTS pllua;        -- triggers e funções Lua no PostgreSQL

-- Schema analítico (CQRS parcial: leitura do dashboard)
CREATE SCHEMA IF NOT EXISTS analytics;
COMMENT ON SCHEMA analytics IS 'Dados pré-calculados para o dashboard';

-- search_path para o Laravel encontrar public e analytics
ALTER DATABASE studytrack SET search_path TO public, analytics;
