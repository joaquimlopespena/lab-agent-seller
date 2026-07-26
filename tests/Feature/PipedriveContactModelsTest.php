<?php

use App\Models\CompanyCustomer;
use App\Models\PipedriveOrganization;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePersonEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pipedrive contact tree and optional factory states are coherent', function () {
    $person = PipedrivePerson::factory()->withOrganization()->withOwner()->create();
    $email = PipedrivePersonEmail::factory()->primary()->create([
        'company_id' => $person->company_id,
        'pipedrive_person_id' => $person,
    ]);
    $customer = CompanyCustomer::factory()->create([
        'company_id' => $person->company_id,
        'pipedrive_person_id' => $person,
        'pipedrive_person_email_id' => $email,
    ]);
    $withoutRelations = PipedrivePerson::factory()->withoutOptionalRelations()->create();

    expect($person->organization)->toBeInstanceOf(PipedriveOrganization::class)
        ->and($person->owner)->not->toBeNull()
        ->and($person->emails->first()->is($email))->toBeTrue()
        ->and($email->person->is($person))->toBeTrue()
        ->and($customer->pipedrivePerson->is($person))->toBeTrue()
        ->and($customer->pipedrivePersonEmail->is($email))->toBeTrue()
        ->and($customer->company_id)->toBe($person->company_id)
        ->and($withoutRelations->organization)->toBeNull()
        ->and($withoutRelations->owner)->toBeNull();
});
