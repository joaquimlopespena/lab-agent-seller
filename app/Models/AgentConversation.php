<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\AgentConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Ai\Models\Conversation;

#[Fillable(['id', 'company_id', 'company_customer_id', 'status_id', 'participant_type', 'participant_id', 'title'])]
class AgentConversation extends Conversation
{
    /** @use HasFactory<AgentConversationFactory> */
    use BelongsToCompany, HasFactory;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyCustomer(): BelongsTo
    {
        return $this->belongsTo(CompanyCustomer::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ConversationStatus::class, 'status_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentConversationMessage::class, 'conversation_id');
    }

    protected function tenantRelationships(): array
    {
        return ['companyCustomer'];
    }
}
