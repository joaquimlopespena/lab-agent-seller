<?php

namespace App\Http\Controllers\Customer;

use App\CustomerAuth\CustomerAuthConfiguration;
use App\Http\Controllers\Controller;
use App\Models\CustomerSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EndCustomerSessionController extends Controller
{
    public function __invoke(
        Request $request,
        CustomerAuthConfiguration $configuration,
    ): RedirectResponse {
        $customerSession = $request->attributes->get('customerSession');

        if ($customerSession instanceof CustomerSession) {
            $customerSession->update(['revoked_at' => now()]);
        }

        return to_route('customer.access.request')
            ->withCookie(cookie()->forget($configuration->cookieName));
    }
}
