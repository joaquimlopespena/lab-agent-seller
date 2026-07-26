<?php

namespace App\Http\Controllers\Customer;

use App\Conversations\CustomerConversationService;
use App\Conversations\OpenCustomerConversation;
use App\Exceptions\AgentReplyGenerationFailed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SendCustomerMessageRequest;
use App\Models\AgentConversationMessage;
use App\Models\CompanyCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerConversationController extends Controller
{
    public function index(
        Request $request,
        OpenCustomerConversation $openCustomerConversation,
        CustomerConversationService $conversationService,
    ): Response {
        $companyCustomer = $this->companyCustomer($request);
        $conversation = $openCustomerConversation->handle($companyCustomer);

        return Inertia::render('customer/chat/index', [
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
            ],
            'messages' => $conversationService->messages($conversation)
                ->map($this->serializeMessage(...))
                ->values(),
        ]);
    }

    public function store(
        SendCustomerMessageRequest $request,
        CustomerConversationService $conversationService,
    ): JsonResponse {
        try {
            $exchange = $conversationService->send(
                $this->companyCustomer($request),
                $request->string('content')->toString(),
            );
        } catch (AgentReplyGenerationFailed $exception) {
            return $this->failureResponse($exception);
        }

        return response()->json([
            'status' => 'replied',
            'messages' => [
                $this->serializeMessage($exchange->userMessage),
                $this->serializeMessage($exchange->assistantMessage),
            ],
        ], 201);
    }

    public function retry(
        Request $request,
        AgentConversationMessage $message,
        CustomerConversationService $conversationService,
    ): JsonResponse {
        try {
            $reply = $conversationService->retry(
                $this->companyCustomer($request),
                $message,
            );
        } catch (AgentReplyGenerationFailed $exception) {
            return $this->failureResponse($exception);
        }

        return response()->json([
            'status' => 'replied',
            'messages' => [$this->serializeMessage($reply)],
        ]);
    }

    private function companyCustomer(Request $request): CompanyCustomer
    {
        $companyCustomer = $request->attributes->get('companyCustomer');

        return $companyCustomer instanceof CompanyCustomer
            ? $companyCustomer
            : abort(404);
    }

    /**
     * @return array{id: string, role: string, content: string, created_at: string}
     */
    private function serializeMessage(AgentConversationMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'created_at' => $message->created_at->toISOString(),
        ];
    }

    private function failureResponse(AgentReplyGenerationFailed $exception): JsonResponse
    {
        report($exception);

        return response()->json([
            'status' => 'provider_failed',
            'retryable' => true,
            'message' => 'Não foi possível gerar a resposta. Tente novamente.',
            'user_message' => $this->serializeMessage($exception->userMessage),
        ], 503);
    }
}
