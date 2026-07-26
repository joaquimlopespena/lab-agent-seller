<?php

namespace App\Http\Controllers\Customer;

use App\CustomerAuth\IssueCustomerMagicLink;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\RequestMagicLinkRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerMagicLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('customer/access/request');
    }

    public function store(
        RequestMagicLinkRequest $request,
        IssueCustomerMagicLink $issueCustomerMagicLink,
    ): RedirectResponse {
        $eligible = $issueCustomerMagicLink->handle($request->validated('email'));

        return to_route('customer.access.request')->with(
            'customerAccess',
            $eligible ? 'sent' : 'not_associated',
        );
    }
}
