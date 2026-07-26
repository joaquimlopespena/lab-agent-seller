<?php

namespace App\Http\Middleware;

use App\CustomerAuth\CustomerCompanyResolver;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\CustomerIdentity;
use App\Models\CustomerSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveCustomerTenant
{
    public function __construct(private CustomerCompanyResolver $customerCompanyResolver) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $customerIdentity = auth('customer')->user();
        $customerSession = $request->attributes->get('customerSession');

        if (! $customerIdentity instanceof CustomerIdentity
            || ! $customerSession instanceof CustomerSession
            || $customerSession->customer_identity_id !== $customerIdentity->id
            || ! $customerSession->isValid()) {
            abort(404);
        }

        if ($customerSession->active_company_id === null) {
            return to_route('customer.companies.index');
        }

        $companyCustomer = $this->customerCompanyResolver->companyCustomer(
            $customerIdentity,
            (int) $customerSession->active_company_id,
        );

        if ($companyCustomer === null) {
            $customerSession->update(['active_company_id' => null]);

            return to_route('customer.companies.index');
        }

        $this->ensureConversationResourcesBelongToTenant(
            $request,
            $companyCustomer->company_id,
            $companyCustomer->id,
        );

        $request->attributes->set('activeCompany', $companyCustomer->company);
        $request->attributes->set('companyCustomer', $companyCustomer);

        return $next($request);
    }

    private function ensureConversationResourcesBelongToTenant(
        Request $request,
        int $companyId,
        int $companyCustomerId,
    ): void {
        $conversation = null;
        $conversationParameter = $request->route('conversation');

        if ($conversationParameter !== null) {
            $conversationId = $conversationParameter instanceof AgentConversation
                ? $conversationParameter->getKey()
                : $conversationParameter;
            $conversation = AgentConversation::query()
                ->whereKey($conversationId)
                ->where('company_id', $companyId)
                ->where('company_customer_id', $companyCustomerId)
                ->firstOrFail();

            $request->route()->setParameter('conversation', $conversation);
        }

        $messageParameter = $request->route('message');

        if ($messageParameter !== null) {
            $messageId = $messageParameter instanceof AgentConversationMessage
                ? $messageParameter->getKey()
                : $messageParameter;
            $message = AgentConversationMessage::query()
                ->whereKey($messageId)
                ->where('company_id', $companyId)
                ->when(
                    $conversation !== null,
                    fn ($query) => $query->where('conversation_id', $conversation->id),
                )
                ->whereHas('conversation', fn ($query) => $query
                    ->where('company_customer_id', $companyCustomerId))
                ->firstOrFail();

            $request->route()->setParameter('message', $message);
        }
    }
}
