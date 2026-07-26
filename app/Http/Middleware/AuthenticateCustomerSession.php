<?php

namespace App\Http\Middleware;

use App\CustomerAuth\CustomerAuthConfiguration;
use App\Models\CustomerSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCustomerSession
{
    public function __construct(private CustomerAuthConfiguration $configuration) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('customer')->guest()) {
            return to_route('customer.access.request')
                ->withCookie(cookie()->forget($this->configuration->cookieName));
        }

        if (! $request->attributes->get('customerSession') instanceof CustomerSession) {
            $token = $request->cookie($this->configuration->cookieName);
            $customerSession = is_string($token)
                ? CustomerSession::query()
                    ->where('token_hash', hash('sha256', $token))
                    ->where('customer_identity_id', auth('customer')->id())
                    ->first()
                : null;

            if ($customerSession === null || ! $customerSession->isValid()) {
                return to_route('customer.access.request')
                    ->withCookie(cookie()->forget($this->configuration->cookieName));
            }

            $customerSession->update(['last_activity_at' => now()]);
            $request->attributes->set('customerSession', $customerSession);
        }

        return $next($request);
    }
}
