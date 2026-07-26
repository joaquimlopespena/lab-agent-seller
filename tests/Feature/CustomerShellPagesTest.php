<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders each customer shell destination without administrative authentication', function (
    string $routeName,
    string $component,
) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->missing('company')
            ->missing('companies')
            ->missing('conversation')
            ->missing('messages')
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
