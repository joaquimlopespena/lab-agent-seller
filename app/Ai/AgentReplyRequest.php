<?php

namespace App\Ai;

final readonly class AgentReplyRequest
{
    /**
     * @param  list<array{role: 'user'|'assistant', content: string}>  $history
     */
    public function __construct(
        public array $history,
        public string $message,
    ) {}
}
