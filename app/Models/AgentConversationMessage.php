<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\AgentConversationMessageFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Ai\Models\ConversationMessage;

/**
 * @property string $id
 * @property string $conversation_id
 * @property int $company_id
 * @property int $message_role_id
 * @property string $role
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MessageRole $messageRole
 */
#[Fillable(['id', 'conversation_id', 'company_id', 'message_role_id', 'participant_type', 'participant_id', 'agent', 'role', 'content', 'attachments', 'tool_calls', 'tool_results', 'usage', 'meta', 'approval_state'])]
class AgentConversationMessage extends ConversationMessage
{
    /** @use HasFactory<AgentConversationMessageFactory> */
    use BelongsToCompany, HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $message): void {
            $canonicalRole = $message->messageRole()->value('slug');

            if ($canonicalRole !== $message->role) {
                throw new DomainException('The compatible role must equal the canonical message role.');
            }
        });
    }

    /** @return BelongsTo<AgentConversation, $this> */
    public function agentConversation(): BelongsTo
    {
        return $this->belongsTo(AgentConversation::class, 'conversation_id');
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<MessageRole, $this> */
    public function messageRole(): BelongsTo
    {
        return $this->belongsTo(MessageRole::class);
    }

    /** @return list<string> */
    protected function tenantRelationships(): array
    {
        return ['agentConversation'];
    }
}
