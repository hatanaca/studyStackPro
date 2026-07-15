<?php

namespace Tests\Feature\LinkedIn;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LinkedInMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_linkedin_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'linkedin_id'));
        $this->assertTrue(Schema::hasColumn('users', 'linkedin_token'));
        $this->assertTrue(Schema::hasColumn('users', 'linkedin_refresh_token'));
        $this->assertTrue(Schema::hasColumn('users', 'linkedin_token_expires_at'));
    }

    public function test_linkedin_id_is_nullable_and_unique(): void
    {
        $column = Schema::getColumnType('users', 'linkedin_id');
        $this->assertNull($column); // nullable columns return null

        // Unique constraint check via indexes
        $indexes = Schema::getIndexes('users');
        $linkedinUnique = collect($indexes)->contains(function ($index) {
            return str_contains($index['name'] ?? '', 'linkedin_id')
                && $index['unique'] === true;
        });
        $this->assertTrue($linkedinUnique, 'linkedin_id should have a unique index');
    }

    public function test_linkedin_token_columns_are_nullable(): void
    {
        $this->assertNull(Schema::getColumnType('users', 'linkedin_token'));
        $this->assertNull(Schema::getColumnType('users', 'linkedin_refresh_token'));
        $this->assertNull(Schema::getColumnType('users', 'linkedin_token_expires_at'));
    }
}
