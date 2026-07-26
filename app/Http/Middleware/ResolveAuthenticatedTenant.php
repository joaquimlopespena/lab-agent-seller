<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAuthenticatedTenant
{
    public function __construct(private TenantContext $tenantContext) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            $this->tenantContext->forget();

            return $next($request);
        }

        $this->tenantContext->setForUser($user);
        $request->attributes->set('currentCompany', $this->tenantContext->company());

        return $next($request);
    }
}
