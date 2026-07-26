<?php

namespace App\Conversations;

use App\Models\AgentConversation;
use App\Models\CompanyCustomer;
use App\Models\ConversationStatus;
use Illuminate\Support\Str;

final class OpenCustomerConversation
{
    public function handle(CompanyCustomer $companyCustomer): AgentConversation
    {
        $status = ConversationStatus::query()->firstOrCreate(
            ['slug' => ConversationStatus::Active],
            ['name' => 'Active', 'is_active' => true],
        );

        if (! $status->is_active) {
            abort(409, 'The active conversation status is unavailable.');
        }

        return AgentConversation::query()->firstOrCreate(
            [
                'company_id' => $companyCustomer->company_id,
                'company_customer_id' => $companyCustomer->id,
            ],
            [
                'id' => (string) Str::uuid7(),
                'status_id' => $status->id,
                'participant_type' => $companyCustomer->customerIdentity->getMorphClass(),
                'participant_id' => $companyCustomer->customer_identity_id,
                'title' => 'Conversa com o agente de vendas',
            ],
        );
    }
}
