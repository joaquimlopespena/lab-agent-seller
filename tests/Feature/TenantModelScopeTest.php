<?php

use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CrmConnection;
use App\Models\CrmSyncRun;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant scope filters administrative and customer owned models explicitly', function () {
    $first = CompanyCustomer::factory()->create();
    CompanyCustomer::factory()->create();
    $conversation = AgentConversation::factory()->create([
        'company_id' => $first->company_id,
        'company_customer_id' => $first,
    ]);

    expect(CompanyCustomer::forCompany($first->company)->get())->toHaveCount(1)
        ->and(AgentConversation::forCompany($first->company)->sole()->is($conversation))->toBeTrue();
});

test('redundant references from another tenant are rejected', function () {
    $firstCompany = Company::factory()->create();
    $otherConnection = CrmConnection::factory()->create();

    CrmSyncRun::factory()->create([
        'company_id' => $firstCompany,
        'crm_connection_id' => $otherConnection,
    ]);
})->throws(DomainException::class);

test('conversation messages cannot cross a company boundary', function () {
    $conversation = AgentConversation::factory()->create();

    AgentConversationMessage::factory()->create([
        'company_id' => Company::factory(),
        'conversation_id' => $conversation,
    ]);
})->throws(DomainException::class);
