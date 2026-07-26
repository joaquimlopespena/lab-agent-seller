<?php

namespace App\Http\Middleware;

use App\CustomerAuth\CustomerAuthConfiguration;
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

        return $next($request);
    }
}
