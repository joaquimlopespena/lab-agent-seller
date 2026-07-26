<?php

namespace App\Exceptions;

use App\Models\AgentConversationMessage;
use RuntimeException;
use Throwable;

final class AgentReplyGenerationFailed extends RuntimeException
{
    public function __construct(
        public readonly AgentConversationMessage $userMessage,
        Throwable $previous,
    ) {
        parent::__construct('The agent provider could not generate a reply.', previous: $previous);
    }
}
