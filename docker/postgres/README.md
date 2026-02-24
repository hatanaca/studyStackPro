# PostgreSQL 16 — StudyTrack Pro

## Estrutura

- **init/** — Scripts executados na primeira inicialização do container (quando o volume de dados está vazio).
  - `01-extensions-and-schema.sql` — Cria as extensões (`pgcrypto`, `pg_trgm`) e o schema `analytics`, e define o `search_path` do banco.

## Observação

Os scripts em `init/` rodam apenas na **primeira** subida do serviço (volume novo). Se o volume `postgres_data` já existir, eles são ignorados. Para rodar de novo, é preciso remover o volume e subir de novo:

```bash
docker compose down -v
make dev
```
