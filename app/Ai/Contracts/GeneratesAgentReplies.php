<?php

namespace App\Ai\Contracts;

use App\Ai\AgentReply;
use App\Ai\AgentReplyRequest;

interface GeneratesAgentReplies
{
    public function generate(AgentReplyRequest $request): AgentReply;
}
