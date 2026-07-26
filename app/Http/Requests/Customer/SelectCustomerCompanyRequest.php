<?php

namespace App\Http\Requests\Customer;

use App\Models\CustomerSession;
use Illuminate\Foundation\Http\FormRequest;

class SelectCustomerCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('customerSession') instanceof CustomerSession;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Escolha uma empresa para continuar.',
            'company_id.integer' => 'A empresa escolhida não está disponível.',
        ];
    }
}
