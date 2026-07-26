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

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Escolha uma empresa para continuar.',
            'company_id.integer' => 'A empresa escolhida não está disponível.',
        ];
    }
}
