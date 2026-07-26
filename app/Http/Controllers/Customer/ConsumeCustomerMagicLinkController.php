<?php

namespace App\Http\Controllers\Customer;

use App\CustomerAuth\ConsumeCustomerMagicLink;
use App\CustomerAuth\CustomerAuthConfiguration;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ConsumeCustomerMagicLinkController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        ConsumeCustomerMagicLink $consumeCustomerMagicLink,
        CustomerAuthConfiguration $configuration,
    ): Response {
        $consumption = $consumeCustomerMagicLink->handle($token, $request);

        if (! $consumption->successful()) {
            return Inertia::render('customer/access/error', [
                'reason' => $consumption->failure === 'expired' ? 'expired' : 'invalid',
            ])->toResponse($request);
        }

        return to_route('customer.access.confirmation')
            ->withCookie(cookie(
                name: $configuration->cookieName,
                value: $consumption->token,
                minutes: $configuration->sessionTtlHours * 60,
                secure: $configuration->cookieSecure,
                httpOnly: true,
                sameSite: $configuration->cookieSameSite,
            ));
    }
}
