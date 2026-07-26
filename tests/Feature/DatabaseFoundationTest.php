<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the complete foundation migrates and seeds on a clean PostgreSQL database', function () {
    expect(Artisan::call('migrate:fresh', ['--seed' => true, '--no-interaction' => true]))->toBe(0);

    $domainTables = [
        'companies', 'crm_connections', 'crm_sync_runs', 'pipedrive_pipelines', 'pipedrive_stages',
        'pipedrive_custom_fields', 'pipedrive_users', 'pipedrive_organizations', 'pipedrive_people',
        'pipedrive_person_emails', 'pipedrive_deals', 'pipedrive_products', 'pipedrive_deal_products',
        'pipedrive_activities', 'customer_identities', 'company_customers', 'customer_magic_links',
        'customer_sessions', 'agent_conversations', 'agent_conversation_messages',
    ];

    foreach ($domainTables as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    expect(DB::table('crm_connection_statuses')->count())->toBe(5)
        ->and(DB::table('message_roles')->count())->toBe(3)
        ->and(DB::connection()->getDriverName())->toBe('pgsql');

    $tenantTables = [
        'users', 'crm_connections', 'crm_sync_runs', 'pipedrive_pipelines', 'pipedrive_stages',
        'pipedrive_custom_fields', 'pipedrive_users', 'pipedrive_organizations', 'pipedrive_people',
        'pipedrive_person_emails', 'pipedrive_deals', 'pipedrive_products', 'pipedrive_activities',
        'company_customers', 'agent_conversations', 'agent_conversation_messages',
    ];

    foreach ($tenantTables as $table) {
        expect(column($table, 'company_id')['nullable'])->toBeFalse()
            ->and(foreignColumns($table))->toMatchArray(['company_id' => 'companies']);
    }

    expect(uniqueIndexColumns('users'))->toContain(['company_id'])
        ->and(uniqueIndexColumns('crm_connections'))->toContain(['company_id'])
        ->and(uniqueIndexColumns('pipedrive_pipelines'))->toContain(['company_id', 'pipedrive_id'])
        ->and(uniqueIndexColumns('company_customers'))->toContain(['company_id', 'customer_identity_id'])
        ->and(uniqueIndexColumns('agent_conversations'))->toContain(['company_id', 'company_customer_id']);

    $lookupSnapshot = collect([
        'crm_connection_statuses',
        'crm_sync_statuses',
        'conversation_statuses',
        'message_roles',
    ])->mapWithKeys(fn (string $table): array => [
        $table => DB::table($table)->orderBy('slug')->pluck('created_at', 'slug')->all(),
    ])->all();

    expect(Artisan::call('db:seed', ['--no-interaction' => true]))->toBe(0)
        ->and(Artisan::call('db:seed', ['--no-interaction' => true]))->toBe(0);

    foreach ($lookupSnapshot as $table => $rows) {
        expect(DB::table($table)->orderBy('slug')->pluck('created_at', 'slug')->all())->toBe($rows);
    }
});
