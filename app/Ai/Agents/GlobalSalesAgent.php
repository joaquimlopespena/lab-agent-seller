<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

final class GlobalSalesAgent implements Agent, Conversational
{
    use Promptable;

    /**
     * @param  list<array{role: 'user'|'assistant', content: string}>  $history
     */
    public function __construct(private readonly array $history = []) {}

    public function instructions(): string
    {
        return (string) config('sales_agent.system_prompt');
    }

    /**
     * @return list<Message>
     */
    public function messages(): iterable
    {
        return array_map(
            fn (array $message): Message => new Message($message['role'], $message['content']),
            $this->history,
        );
    }
}
