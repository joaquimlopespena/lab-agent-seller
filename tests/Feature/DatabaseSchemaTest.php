<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('lookup tables and companies match the approved schema', function () {
    foreach (['crm_connection_statuses', 'crm_sync_statuses', 'conversation_statuses', 'message_roles'] as $table) {
        expect(Schema::hasColumns($table, ['id', 'name', 'slug', 'description', 'is_active', 'created_at', 'updated_at']))->toBeTrue()
            ->and(uniqueIndexColumns($table))->toContain(['slug'])
            ->and(column($table, 'description')['nullable'])->toBeTrue()
            ->and(column($table, 'is_active')['default'])->toBe('true');
    }

    expect(Schema::hasColumns('companies', ['id', 'name', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumn('companies', 'deleted_at'))->toBeFalse();
});
