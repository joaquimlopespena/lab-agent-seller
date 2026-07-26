<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('pipedrive structure tables preserve sync metadata and compound keys', function () {
    foreach (['pipedrive_pipelines', 'pipedrive_stages', 'pipedrive_custom_fields', 'pipedrive_users'] as $table) {
        expect(Schema::hasColumns($table, ['company_id', 'crm_connection_id', 'pipedrive_id', 'raw_payload', 'synced_at']))->toBeTrue()
            ->and(foreignColumns($table))->toMatchArray(['company_id' => 'companies', 'crm_connection_id' => 'crm_connections']);
    }

    expect(uniqueIndexColumns('pipedrive_pipelines'))->toContain(['company_id', 'pipedrive_id'])
        ->and(uniqueIndexColumns('pipedrive_stages'))->toContain(['company_id', 'pipedrive_id'])
        ->and(indexColumns('pipedrive_stages'))->toContain(['pipedrive_pipeline_id', 'display_order'])
        ->and(uniqueIndexColumns('pipedrive_custom_fields'))->toContain(['company_id', 'entity_type', 'pipedrive_key'])
        ->and(uniqueIndexColumns('pipedrive_users'))->toContain(['company_id', 'pipedrive_id']);
});
