<?php

namespace App\Http\Controllers\Customer;

use App\CustomerAuth\CustomerCompanyResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SelectCustomerCompanyRequest;
use App\Models\CustomerIdentity;
use App\Models\CustomerSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerCompanyController extends Controller
{
    public function __construct(private CustomerCompanyResolver $customerCompanyResolver) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $customerIdentity = $this->customerIdentity();
        $customerSession = $this->customerSession($request);
        $availableCompanies = $this->customerCompanyResolver->availableCompanies($customerIdentity);

        if ($availableCompanies->count() === 1) {
            $this->customerCompanyResolver->automaticallySelectOnlyCompany(
                $customerSession,
                $customerIdentity,
            );

            return to_route('customer.chat.index');
        }

        return Inertia::render('customer/companies/index', [
            'companies' => $availableCompanies->map->only(['id', 'name'])->values(),
        ]);
    }

    public function store(SelectCustomerCompanyRequest $request): RedirectResponse
    {
        $customerIdentity = $this->customerIdentity();
        $customerSession = $this->customerSession($request);
        $companyCustomer = $this->customerCompanyResolver->companyCustomer(
            $customerIdentity,
            $request->integer('company_id'),
        );

        if ($companyCustomer === null) {
            throw ValidationException::withMessages([
                'company_id' => 'A empresa escolhida não está mais disponível.',
            ]);
        }

        $customerSession->update(['active_company_id' => $companyCustomer->company_id]);

        return to_route('customer.chat.index');
    }

    private function customerIdentity(): CustomerIdentity
    {
        $customerIdentity = auth('customer')->user();

        abort_unless($customerIdentity instanceof CustomerIdentity, 401);

        return $customerIdentity;
    }

    private function customerSession(Request $request): CustomerSession
    {
        $customerSession = $request->attributes->get('customerSession');

        abort_unless($customerSession instanceof CustomerSession, 401);

        return $customerSession;
    }
}
