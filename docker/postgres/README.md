# PostgreSQL 16 — StudyTrack Pro

## Structure

- **init/** — Scripts executed on the first container startup (when the data volume is empty).
  - `01-extensions-and-schema.sql` — Creates extensions (`pgcrypto`, `pg_trgm`) and the `analytics` schema, and sets the database `search_path`.

## Note

The scripts in `init/` run only on the **first** service startup (new volume). If the `postgres_data` volume already exists, they are ignored. To run them again, you need to remove the volume and start fresh:

```bash
docker compose down -v
make dev
```
