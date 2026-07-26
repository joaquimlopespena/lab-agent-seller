<?php

namespace App\Conversations;

use App\Ai\AgentReplyRequest;
use App\Ai\Agents\GlobalSalesAgent;
use App\Ai\Contracts\GeneratesAgentReplies;
use App\Exceptions\AgentReplyGenerationFailed;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\CompanyCustomer;
use App\Models\MessageRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class CustomerConversationService
{
    public function __construct(
        private readonly OpenCustomerConversation $openCustomerConversation,
        private readonly GeneratesAgentReplies $replyGenerator,
    ) {}

    public function send(CompanyCustomer $companyCustomer, string $content): CustomerConversationExchange
    {
        $conversation = $this->openCustomerConversation->handle($companyCustomer);
        $userMessage = $this->createUserMessage($conversation, $companyCustomer, $content);
        $assistantMessage = $this->generateReply($conversation, $userMessage);

        return new CustomerConversationExchange($userMessage, $assistantMessage);
    }

    public function retry(
        CompanyCustomer $companyCustomer,
        AgentConversationMessage $userMessage,
    ): AgentConversationMessage {
        $conversation = AgentConversation::query()
            ->whereKey($userMessage->conversation_id)
            ->where('company_id', $companyCustomer->company_id)
            ->where('company_customer_id', $companyCustomer->id)
            ->firstOrFail();

        if ($userMessage->role !== MessageRole::User
            || $this->orderedMessages($conversation)->last()?->id !== $userMessage->id) {
            abort(409, 'This message cannot be retried.');
        }

        return $this->generateReply($conversation, $userMessage);
    }

    /**
     * @return Collection<int, AgentConversationMessage>
     */
    public function messages(AgentConversation $conversation): Collection
    {
        return $this->orderedMessages($conversation);
    }

    private function createUserMessage(
        AgentConversation $conversation,
        CompanyCustomer $companyCustomer,
        string $content,
    ): AgentConversationMessage {
        $role = $this->activeRole(MessageRole::User);

        return AgentConversationMessage::query()->create([
            'id' => (string) Str::uuid7(),
            'conversation_id' => $conversation->id,
            'company_id' => $conversation->company_id,
            'message_role_id' => $role->id,
            'participant_type' => $companyCustomer->customerIdentity->getMorphClass(),
            'participant_id' => $companyCustomer->customer_identity_id,
            'agent' => GlobalSalesAgent::class,
            'role' => MessageRole::User,
            'content' => $content,
            'attachments' => [],
            'tool_calls' => [],
            'tool_results' => [],
            'usage' => [],
            'meta' => [],
            'approval_state' => null,
        ]);
    }

    private function generateReply(
        AgentConversation $conversation,
        AgentConversationMessage $userMessage,
    ): AgentConversationMessage {
        $history = [];

        foreach ($this->orderedMessages($conversation) as $message) {
            if ($message->is($userMessage)) {
                break;
            }

            $history[] = [
                'role' => $message->role === MessageRole::Assistant
                    ? MessageRole::Assistant
                    : MessageRole::User,
                'content' => $message->content,
            ];
        }

        try {
            $reply = $this->replyGenerator->generate(new AgentReplyRequest(
                history: $history,
                message: $userMessage->content,
            ));
        } catch (Throwable $exception) {
            throw new AgentReplyGenerationFailed($userMessage, $exception);
        }

        return DB::transaction(function () use ($conversation, $userMessage, $reply): AgentConversationMessage {
            AgentConversation::query()->whereKey($conversation->id)->lockForUpdate()->firstOrFail();

            if ($this->orderedMessages($conversation)->last()?->id !== $userMessage->id) {
                abort(409, 'The conversation changed while the reply was generated.');
            }

            $role = $this->activeRole(MessageRole::Assistant);

            return AgentConversationMessage::query()->create([
                'id' => (string) Str::uuid7(),
                'conversation_id' => $conversation->id,
                'company_id' => $conversation->company_id,
                'message_role_id' => $role->id,
                'participant_type' => null,
                'participant_id' => null,
                'agent' => GlobalSalesAgent::class,
                'role' => MessageRole::Assistant,
                'content' => $reply->content,
                'attachments' => [],
                'tool_calls' => [],
                'tool_results' => [],
                'usage' => [],
                'meta' => [],
                'approval_state' => null,
            ]);
        });
    }

    private function activeRole(string $slug): MessageRole
    {
        return MessageRole::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, AgentConversationMessage>
     */
    private function orderedMessages(AgentConversation $conversation): Collection
    {
        return $conversation->agentMessages()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
