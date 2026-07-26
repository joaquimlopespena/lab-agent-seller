<?php

use App\Ai\AgentReplyRequest;
use App\Ai\Agents\GlobalSalesAgent;
use App\Ai\LaravelAiReplyGenerator;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Prompts\AgentPrompt;

it('sends only the global prompt authorized history and current message without tools or crm context', function () {
    config()->set('sales_agent.provider', 'openai');
    config()->set('sales_agent.model', 'gpt-4.1-mini');

    GlobalSalesAgent::fake(['Vamos avançar.'])->preventStrayPrompts();

    $reply = app(LaravelAiReplyGenerator::class)->generate(new AgentReplyRequest(
        history: [
            ['role' => 'user', 'content' => 'Olá'],
            ['role' => 'assistant', 'content' => 'Como posso ajudar?'],
        ],
        message: 'Qual é o próximo passo?',
    ));

    GlobalSalesAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        expect($prompt->prompt)->toBe('Qual é o próximo passo?')
            ->and($prompt->model)->toBe('gpt-4.1-mini')
            ->and($prompt->agent->instructions())->toBe(config('sales_agent.system_prompt'))
            ->and($prompt->agent->messages())->toHaveCount(2)
            ->and($prompt->agent)->not->toBeInstanceOf(HasTools::class)
            ->and(collect($prompt->agent->messages())->map->content->all())
            ->toBe(['Olá', 'Como posso ajudar?'])
            ->and($prompt->agent->instructions())->not->toContain('Pipedrive', 'tenant_id', 'company_id');

        return true;
    });

    expect($reply->content)->toBe('Vamos avançar.');
});

it('keeps the system prompt global even when tenant-shaped values are present', function () {
    $agent = new GlobalSalesAgent;

    expect($agent->instructions())->toBe(config('sales_agent.system_prompt'))
        ->and(config('sales_agent'))->not->toHaveKeys(['company', 'tenant', 'prompt_override']);
});
