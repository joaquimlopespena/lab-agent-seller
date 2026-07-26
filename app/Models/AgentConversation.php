<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\AgentConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Laravel\Ai\Models\Conversation;

/**
 * @property string $id
 * @property int $company_id
 * @property int $company_customer_id
 * @property int $status_id
 * @property string|null $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CompanyCustomer $companyCustomer
 * @property-read ConversationStatus $status
 * @property-read Collection<int, AgentConversationMessage> $agentMessages
 */
#[Fillable(['id', 'company_id', 'company_customer_id', 'status_id', 'participant_type', 'participant_id', 'title'])]
class AgentConversation extends Conversation
{
    /** @use HasFactory<AgentConversationFactory> */
    use BelongsToCompany, HasFactory;

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<CompanyCustomer, $this> */
    public function companyCustomer(): BelongsTo
    {
        return $this->belongsTo(CompanyCustomer::class);
    }

    /** @return BelongsTo<ConversationStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ConversationStatus::class, 'status_id');
    }

    /** @return HasMany<AgentConversationMessage, $this> */
    public function agentMessages(): HasMany
    {
        return $this->hasMany(AgentConversationMessage::class, 'conversation_id');
    }

    /** @return list<string> */
    protected function tenantRelationships(): array
    {
        return ['companyCustomer'];
    }
}
