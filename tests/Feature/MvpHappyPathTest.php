<?php

use App\Ai\Contracts\GeneratesAgentReplies;
use App\CustomerAuth\CustomerMagicLinkSender;
use App\Integrations\Pipedrive\PipedriveClient;
use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Integrations\Pipedrive\PipedriveToken;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CrmConnection;
use App\Models\CrmSyncStatus;
use App\Models\CustomerMagicLink;
use App\Models\CustomerSession;
use App\Models\PipedrivePerson;
use App\Models\User;
use App\Services\PipedriveSynchronizationService;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\FakeAgentReplyGenerator;
use Tests\FakePipedriveDataClient;

uses(RefreshDatabase::class);

it('executes the complete MVP happy path using only fakes', function () {
    $this->seed(LookupSeeder::class);
    $this->withCredentials();
    $capturedToken = null;

    app()->instance(PipedriveClient::class, new class implements PipedriveClient
    {
        public function authorizationUrl(string $state): string
        {
            return 'https://pipedrive.test/oauth?state='.urlencode($state);
        }

        public function exchangeAuthorizationCode(string $code): PipedriveToken
        {
            return new PipedriveToken('fake-access', 'fake-refresh', now()->addHour()->toDateTimeImmutable(), 'fake-company');
        }
    });
    app()->instance(CustomerMagicLinkSender::class, new class($capturedToken) implements CustomerMagicLinkSender
    {
        public function __construct(private mixed &$capturedToken) {}

        public function send(string $email, string $token, DateTimeInterface $expiresAt): void
        {
            $this->capturedToken = $token;
        }
    });
    app()->instance(GeneratesAgentReplies::class, new FakeAgentReplyGenerator('Olá! Como posso ajudar?'));

    $this->post(route('register.store'), [
        'company_name' => 'Empresa MVP',
        'name' => 'Admin MVP',
        'email' => 'admin@mvp.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $administrator = User::query()->where('email', 'admin@mvp.test')->firstOrFail();
    $company = Company::query()->where('name', 'Empresa MVP')->firstOrFail();
    expect($administrator->company_id)->toBe($company->id);

    $location = $this->actingAs($administrator)->get(route('oauth.pipedrive.start'))->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $oauthQuery);
    $this->get(route('oauth.pipedrive.callback', [
        'state' => $oauthQuery['state'],
        'code' => 'fake-code',
    ]))->assertRedirect(route('admin.integrations.index'));

    $connection = CrmConnection::query()->whereBelongsTo($company)->firstOrFail();
    expect($connection->access_token)->toBe('fake-access');

    $pipedrive = (new FakePipedriveDataClient)->with('persons', [[
        'id' => 501,
        'name' => 'Cliente MVP',
        'active_flag' => true,
        'email' => [['value' => 'cliente@mvp.test', 'primary' => true, 'label' => 'work']],
    ]]);
    app()->instance(PipedriveDataClient::class, $pipedrive);
    $sync = app(PipedriveSynchronizationService::class)->synchronize($company, $connection);

    expect($sync->status->slug)->toBe(CrmSyncStatus::Succeeded)
        ->and(PipedrivePerson::query()->forCompany($company)->count())->toBe(1)
        ->and(CompanyCustomer::query()->forCompany($company)->count())->toBe(1);

    $this->post(route('customer.access.store'), ['email' => 'cliente@mvp.test'])
        ->assertSessionHas('customerAccess', 'sent');
    expect(CustomerMagicLink::query()->count())->toBe(1)
        ->and($capturedToken)->toBeString()->toHaveLength(64);

    $consume = $this->get(route('customer.access.consume', ['token' => $capturedToken]))
        ->assertRedirect(route('customer.access.confirmation'));
    $customerSessionToken = $consume->getCookie('customer_session')?->getValue();
    expect($customerSessionToken)->toBeString();

    $customer = CompanyCustomer::query()->forCompany($company)->sole();
    $this->withCookie('customer_session', $customerSessionToken)
        ->post(route('customer.companies.store'), ['company_id' => $company->id])
        ->assertRedirect(route('customer.chat.index'));

    $this->withCookie('customer_session', $customerSessionToken)
        ->postJson(route('customer.chat.messages.store'), ['content' => 'Quero conhecer o produto'])
        ->assertCreated()
        ->assertJsonPath('messages.1.content', 'Olá! Como posso ajudar?');

    $conversation = AgentConversation::query()->where('company_customer_id', $customer->id)->sole();
    expect(CustomerSession::query()->sole()->active_company_id)->toBe($company->id)
        ->and(AgentConversationMessage::query()->where('conversation_id', $conversation->id)->count())->toBe(2);

    $this->actingAs($administrator)
        ->get(route('admin.conversations.index'))
        ->assertSee('Cliente MVP');
    $this->actingAs($administrator)
        ->get(route('admin.conversations.show', $conversation))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.messages.0.content', 'Quero conhecer o produto')
            ->where('conversation.messages.1.content', 'Olá! Como posso ajudar?')
        );
});
