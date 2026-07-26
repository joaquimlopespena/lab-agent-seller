<?php

namespace App\Conversations;

use App\Models\AgentConversationMessage;

final readonly class CustomerConversationExchange
{
    public function __construct(
        public AgentConversationMessage $userMessage,
        public AgentConversationMessage $assistantMessage,
    ) {}
}
