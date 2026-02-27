<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Habilita extensões PostgreSQL necessárias conforme spec:
     * - pgcrypto: gen_random_uuid()
     * - pg_trgm: busca por trigrama (tecnologias)
     * - btree_gist: índices de exclusão temporal
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pg_trgm"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "btree_gist"');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS "btree_gist"');
        DB::statement('DROP EXTENSION IF EXISTS "pg_trgm"');
        DB::statement('DROP EXTENSION IF EXISTS "pgcrypto"');
    }
};
