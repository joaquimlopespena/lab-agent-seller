<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    private const PerPage = 15;

    public function index(Request $request, TenantContext $tenantContext): Response
    {
        $search = trim((string) $request->query('search', ''));

        abort_if(mb_strlen($search) > 100, 422);

        $conversations = AgentConversation::query()
            ->forCompany($tenantContext->company())
            ->select(['id', 'company_id', 'company_customer_id', 'status_id', 'created_at', 'updated_at'])
            ->with([
                'status:id,name,slug',
                'companyCustomer:id,company_id,customer_identity_id,pipedrive_person_id',
                'companyCustomer.customerIdentity:id,email',
                'companyCustomer.pipedrivePerson:id,company_id,name',
            ])
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->whereHas(
                    'companyCustomer.customerIdentity',
                    fn ($identityQuery) => $identityQuery->where('email', 'ilike', "%{$search}%"),
                )->orWhereHas(
                    'companyCustomer.pipedrivePerson',
                    fn ($personQuery) => $personQuery->where('name', 'ilike', "%{$search}%"),
                );
            }))
            ->latest('updated_at')
            ->paginate(self::PerPage)
            ->withQueryString();

        return Inertia::render('admin/conversations/index', [
            'filters' => ['search' => $search],
            'conversations' => [
                'data' => collect($conversations->items())->map(fn (AgentConversation $conversation): array => $this->conversationSummary($conversation))->values(),
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
                'from' => $conversations->firstItem(),
                'to' => $conversations->lastItem(),
            ],
            'readOnly' => true,
        ]);
    }

    public function show(TenantContext $tenantContext, string $conversation): Response
    {
        $conversationModel = AgentConversation::query()
            ->forCompany($tenantContext->company())
            ->select(['id', 'company_id', 'company_customer_id', 'status_id', 'created_at', 'updated_at'])
            ->with([
                'status:id,name,slug',
                'companyCustomer:id,company_id,customer_identity_id,pipedrive_person_id',
                'companyCustomer.customerIdentity:id,email',
                'companyCustomer.pipedrivePerson:id,company_id,name',
                'messages' => fn ($query) => $query
                    ->select(['id', 'conversation_id', 'company_id', 'message_role_id', 'content', 'created_at', 'updated_at'])
                    ->with('messageRole:id,name,slug')
                    ->oldest('created_at')
                    ->oldest('id'),
            ])
            ->findOrFail($conversation);

        return Inertia::render('admin/conversations/show', [
            'conversation' => [
                ...$this->conversationSummary($conversationModel),
                'messages' => $conversationModel->messages->map(fn (AgentConversationMessage $message): array => [
                    'id' => $message->id,
                    'role' => $message->messageRole->slug,
                    'role_label' => $message->messageRole->name,
                    'content' => $message->content,
                    'created_at' => $message->created_at?->toISOString(),
                    'updated_at' => $message->updated_at?->toISOString(),
                ])->values(),
            ],
            'readOnly' => true,
        ]);
    }

    /**
     * @return array{id: string, customer: array{name: string, email: string}, status: array{name: string, slug: string}, created_at: ?string, updated_at: ?string}
     */
    private function conversationSummary(AgentConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'customer' => [
                'name' => $conversation->companyCustomer->pipedrivePerson->name,
                'email' => $conversation->companyCustomer->customerIdentity->email,
            ],
            'status' => [
                'name' => $conversation->status->name,
                'slug' => $conversation->status->slug,
            ],
            'created_at' => $conversation->created_at?->toISOString(),
            'updated_at' => $conversation->updated_at?->toISOString(),
        ];
    }
}
