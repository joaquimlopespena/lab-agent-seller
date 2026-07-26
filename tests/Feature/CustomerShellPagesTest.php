<?php

use App\Models\CompanyCustomer;
use App\Models\CustomerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('renders each customer shell destination without administrative authentication', function (
    string $routeName,
    string $component,
) {
    $request = $this;

    if ($routeName !== 'customer.access.request') {
        $token = str()->random(64);
        $customerSession = CustomerSession::factory()->create([
            'token_hash' => hash('sha256', $token),
        ]);

        if ($routeName === 'customer.chat.index') {
            $companyCustomer = CompanyCustomer::factory()
                ->for($customerSession->customerIdentity, 'customerIdentity')
                ->create();
            $customerSession->update([
                'active_company_id' => $companyCustomer->company_id,
            ]);
        }

        $request = $this->withCookie('customer_session', $token);
    }

    $request->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->missing('company')
            ->when(
                $routeName === 'customer.companies.index',
                fn (Assert $page) => $page->where('companies', []),
                fn (Assert $page) => $page->missing('companies'),
            )
            ->when(
                $routeName === 'customer.chat.index',
                fn (Assert $page) => $page
                    ->has('conversation.id')
                    ->has('conversation.title')
                    ->where('messages', []),
                fn (Assert $page) => $page
                    ->missing('conversation')
                    ->missing('messages'),
            )
            ->missing('token')
            ->missing('hash')
            ->missing('raw_payload'));
})->with([
    'access request' => [
        'customer.access.request',
        'customer/access/request',
    ],
    'company selection' => [
        'customer.companies.index',
        'customer/companies/index',
    ],
    'chat' => [
        'customer.chat.index',
        'customer/chat/index',
    ],
]);
