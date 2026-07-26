<?php

namespace App\Ai;

use App\Ai\Agents\GlobalSalesAgent;
use App\Ai\Contracts\GeneratesAgentReplies;

final class LaravelAiReplyGenerator implements GeneratesAgentReplies
{
    public function generate(AgentReplyRequest $request): AgentReply
    {
        $response = (new GlobalSalesAgent($request->history))->prompt(
            $request->message,
            provider: (string) config('sales_agent.provider'),
            model: (string) config('sales_agent.model'),
            timeout: (int) config('sales_agent.timeout'),
        );

        return new AgentReply($response->text);
    }
}
