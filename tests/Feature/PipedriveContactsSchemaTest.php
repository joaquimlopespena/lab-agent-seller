<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pipedrive contacts preserve optional relationships and email identity rules', function () {
    expect(column('pipedrive_organizations', 'owner_pipedrive_user_id')['nullable'])->toBeTrue()
        ->and(column('pipedrive_people', 'pipedrive_organization_id')['nullable'])->toBeTrue()
        ->and(column('pipedrive_people', 'owner_pipedrive_user_id')['nullable'])->toBeTrue()
        ->and(uniqueIndexColumns('pipedrive_organizations'))->toContain(['company_id', 'pipedrive_id'])
        ->and(uniqueIndexColumns('pipedrive_people'))->toContain(['company_id', 'pipedrive_id'])
        ->and(indexColumns('pipedrive_person_emails'))->toContain(['company_id', 'email'])
        ->and(uniqueIndexColumns('pipedrive_person_emails'))->toContain(['pipedrive_person_id', 'email']);
});
