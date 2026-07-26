<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('crm connection and sync run constraints are tenant aware', function () {
    expect(uniqueIndexColumns('crm_connections'))->toContain(['company_id'])
        ->and(column('crm_connections', 'provider')['default'])->toContain('pipedrive')
        ->and(foreignColumns('crm_connections'))->toMatchArray([
            'company_id' => 'companies', 'status_id' => 'crm_connection_statuses',
        ])
        ->and(foreignColumns('crm_sync_runs'))->toMatchArray([
            'company_id' => 'companies', 'crm_connection_id' => 'crm_connections', 'status_id' => 'crm_sync_statuses',
        ])
        ->and(indexColumns('crm_sync_runs'))->toContain(['company_id', 'created_at'], ['crm_connection_id', 'status_id']);
});
