<?php

use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\CustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function activeTenantContext(): array
{
    $identity = CustomerIdentity::factory()->create();
    $companyCustomer = CompanyCustomer::factory()
        ->for($identity, 'customerIdentity')
        ->create();
    $token = str()->random(64);
    $session = CustomerSession::factory()
        ->for($identity, 'customerIdentity')
        ->forActiveCompany($companyCustomer->company)
        ->create(['token_hash' => hash('sha256', $token)]);

    return [$identity, $companyCustomer, $session, $token];
}

test('active tenant middleware requires a current selection', function () {
    [, , $session, $token] = activeTenantContext();
    $session->update(['active_company_id' => null]);

    $this->withCookie('customer_session', $token)
        ->get(route('customer.chat.index'))
        ->assertRedirect(route('customer.companies.index'));
});

test('expired customer session is rejected before tenant access', function () {
    [, , $session, $token] = activeTenantContext();
    $session->update(['expires_at' => now()->subMinute()]);

    $this->withCookie('customer_session', $token)
        ->get(route('customer.chat.index'))
        ->assertRedirect(route('customer.access.request'))
        ->assertCookieExpired('customer_session');
});

test('removed current association clears the active tenant and blocks chat', function () {
    [, $companyCustomer, $session, $token] = activeTenantContext();
    $companyCustomer->delete();

    $this->withCookie('customer_session', $token)
        ->get(route('customer.chat.index'))
        ->assertRedirect(route('customer.companies.index'));

    expect($session->fresh()->active_company_id)->toBeNull();
});

test('conversation and message identifiers from another tenant return not found', function () {
    [, $companyCustomer, , $token] = activeTenantContext();
    $otherConversation = AgentConversation::factory()->create();
    $otherMessage = AgentConversationMessage::factory()
        ->for($otherConversation, 'conversation')
        ->for($otherConversation->company, 'company')
        ->create();

    Route::middleware(['web', 'customer.auth', 'customer.tenant'])
        ->get(
            '/testing/customer/conversations/{conversation}/messages/{message}',
            fn (AgentConversation $conversation, AgentConversationMessage $message) => [
                'conversation' => $conversation->title,
                'message' => $message->content,
            ],
        );

    $this->withCookie('customer_session', $token)
        ->get("/testing/customer/conversations/{$otherConversation->id}/messages/{$otherMessage->id}")
        ->assertNotFound()
        ->assertDontSee($otherConversation->title)
        ->assertDontSee($otherMessage->content);

    $ownConversation = AgentConversation::factory()
        ->for($companyCustomer->company, 'company')
        ->for($companyCustomer, 'companyCustomer')
        ->create();
    $ownMessage = AgentConversationMessage::factory()
        ->for($ownConversation, 'conversation')
        ->for($companyCustomer->company, 'company')
        ->create();

    $this->withCookie('customer_session', $token)
        ->get("/testing/customer/conversations/{$ownConversation->id}/messages/{$ownMessage->id}")
        ->assertOk()
        ->assertJsonPath('conversation', $ownConversation->title)
        ->assertJsonPath('message', $ownMessage->content);
});
