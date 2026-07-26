<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('customer identity links and sessions enforce token and tenant keys', function () {
    expect(uniqueIndexColumns('customer_identities'))->toContain(['email'])
        ->and(uniqueIndexColumns('company_customers'))->toContain(
            ['company_id', 'customer_identity_id'], ['company_id', 'pipedrive_person_email_id'],
        )
        ->and(uniqueIndexColumns('customer_magic_links'))->toContain(['token_hash'])
        ->and(indexColumns('customer_magic_links'))->toContain(['customer_identity_id', 'expires_at'])
        ->and(uniqueIndexColumns('customer_sessions'))->toContain(['token_hash'])
        ->and(indexColumns('customer_sessions'))->toContain(
            ['customer_identity_id', 'expires_at'], ['active_company_id', 'customer_identity_id'],
        )
        ->and(column('customer_sessions', 'active_company_id')['nullable'])->toBeTrue();
});
