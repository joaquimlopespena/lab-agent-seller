<?php

namespace Tests;

use App\Ai\AgentReply;
use App\Ai\AgentReplyRequest;
use App\Ai\Contracts\GeneratesAgentReplies;
use Throwable;

class FakeAgentReplyGenerator implements GeneratesAgentReplies
{
    /** @var list<AgentReplyRequest> */
    public array $requests = [];

    /** @var list<string|Throwable> */
    private array $responses;

    public function __construct(string|Throwable ...$responses)
    {
        $this->responses = $responses;
    }

    public function generate(AgentReplyRequest $request): AgentReply
    {
        $this->requests[] = $request;
        $response = array_shift($this->responses) ?? 'Resposta do agente';

        if ($response instanceof Throwable) {
            throw $response;
        }

        return new AgentReply($response);
    }
}
