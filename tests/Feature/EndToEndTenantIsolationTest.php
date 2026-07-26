<?php

use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CrmSyncRun;
use App\Models\CustomerIdentity;
use App\Models\CustomerSession;
use App\Models\PipedriveActivity;
use App\Models\PipedriveCustomField;
use App\Models\PipedriveDeal;
use App\Models\PipedriveOrganization;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePersonEmail;
use App\Models\PipedrivePipeline;
use App\Models\PipedriveProduct;
use App\Models\PipedriveStage;
use App\Models\PipedriveUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\FakePipedriveDataClient;

uses(RefreshDatabase::class);

it('enforces tenant isolation across workflows one through six', function () {
    $this->withCredentials();

    $sharedIdentity = CustomerIdentity::factory()->create(['email' => 'shared@example.test']);
    $firstCompany = Company::factory()->create(['name' => 'Tenant A']);
    $secondCompany = Company::factory()->create(['name' => 'Tenant B']);
    $firstAdmin = User::factory()->for($firstCompany)->create();
    $secondAdmin = User::factory()->for($secondCompany)->create();
    $firstConnection = activePipedriveConnection($firstCompany);
    $secondConnection = activePipedriveConnection($secondCompany);

    $categories = [
        'pipelines' => PipedrivePipeline::class,
        'stages' => PipedriveStage::class,
        'custom-fields' => PipedriveCustomField::class,
        'users' => PipedriveUser::class,
        'organizations' => PipedriveOrganization::class,
        'people' => PipedrivePerson::class,
        'deals' => PipedriveDeal::class,
        'products' => PipedriveProduct::class,
        'activities' => PipedriveActivity::class,
    ];
    $ownedRecords = [];
    $foreignRecords = [];

    foreach ($categories as $category => $model) {
        $ownedRecords[$category] = $model::factory()->create([
            'company_id' => $firstCompany,
            'crm_connection_id' => $firstConnection,
        ]);
        $foreignRecords[$category] = $model::factory()->create([
            'company_id' => $secondCompany,
            'crm_connection_id' => $secondConnection,
        ]);
    }

    $firstPerson = PipedrivePerson::factory()->create([
        'company_id' => $firstCompany,
        'crm_connection_id' => $firstConnection,
    ]);
    $secondPerson = PipedrivePerson::factory()->create([
        'company_id' => $secondCompany,
        'crm_connection_id' => $secondConnection,
    ]);
    $firstEmail = PipedrivePersonEmail::factory()->create([
        'company_id' => $firstCompany,
        'pipedrive_person_id' => $firstPerson,
        'email' => $sharedIdentity->email,
    ]);
    $secondEmail = PipedrivePersonEmail::factory()->create([
        'company_id' => $secondCompany,
        'pipedrive_person_id' => $secondPerson,
        'email' => $sharedIdentity->email,
    ]);
    $firstCustomer = CompanyCustomer::factory()->create([
        'company_id' => $firstCompany,
        'customer_identity_id' => $sharedIdentity,
        'pipedrive_person_id' => $firstPerson,
        'pipedrive_person_email_id' => $firstEmail,
    ]);
    $secondCustomer = CompanyCustomer::factory()->create([
        'company_id' => $secondCompany,
        'customer_identity_id' => $sharedIdentity,
        'pipedrive_person_id' => $secondPerson,
        'pipedrive_person_email_id' => $secondEmail,
    ]);
    $firstConversation = AgentConversation::factory()->create([
        'company_id' => $firstCompany,
        'company_customer_id' => $firstCustomer,
    ]);
    $secondConversation = AgentConversation::factory()->create([
        'company_id' => $secondCompany,
        'company_customer_id' => $secondCustomer,
    ]);
    $firstMessage = AgentConversationMessage::factory()->create([
        'company_id' => $firstCompany,
        'conversation_id' => $firstConversation,
        'content' => 'mensagem do tenant A',
    ]);
    $secondMessage = AgentConversationMessage::factory()->create([
        'company_id' => $secondCompany,
        'conversation_id' => $secondConversation,
        'content' => 'mensagem secreta do tenant B',
    ]);
    $token = str()->random(64);
    $session = CustomerSession::factory()->for($sharedIdentity, 'customerIdentity')->create([
        'active_company_id' => $firstCompany,
        'token_hash' => hash('sha256', $token),
    ]);

    foreach ($categories as $category => $model) {
        $this->actingAs($firstAdmin)
            ->get(route('admin.synced-data.show', [
                'category' => $category,
                'record' => $ownedRecords[$category],
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('record.id', $ownedRecords[$category]->getKey())
                ->missing('record.company_id')
                ->missing('record.crm_connection_id'));

        $this->actingAs($firstAdmin)
            ->get(route('admin.synced-data.show', [
                'category' => $category,
                'record' => $foreignRecords[$category],
            ]))
            ->assertNotFound();
    }

    seedSyncStatuses();
    $synchronizedExternalId = 987654;
    $pipedrive = (new FakePipedriveDataClient)
        ->with('pipelines', [[
            'id' => $synchronizedExternalId,
            'name' => 'Pipeline compartilhado por ID externo',
        ]]);
    app()->instance(PipedriveDataClient::class, $pipedrive);

    $this->actingAs($firstAdmin)
        ->postJson(route('admin.integrations.pipedrive.synchronize'))
        ->assertCreated();

    foreach ($pipedrive->calls as $call) {
        expect($call)->toStartWith($firstCompany->id.':');
    }

    $pipedrive->calls = [];

    $this->actingAs($secondAdmin)
        ->postJson(route('admin.integrations.pipedrive.synchronize'))
        ->assertCreated();

    foreach ($pipedrive->calls as $call) {
        expect($call)->toStartWith($secondCompany->id.':');
    }

    expect(PipedrivePipeline::query()
        ->where('pipedrive_id', $synchronizedExternalId)
        ->pluck('company_id')
        ->sort()
        ->values()
        ->all())->toBe(collect([$firstCompany->id, $secondCompany->id])->sort()->values()->all())
        ->and(CrmSyncRun::query()->whereBelongsTo($firstCompany)->count())->toBe(1)
        ->and(CrmSyncRun::query()->whereBelongsTo($secondCompany)->count())->toBe(1);

    $this->actingAs($firstAdmin)
        ->get(route('admin.conversations.show', $secondConversation))
        ->assertNotFound();
    $this->actingAs($firstAdmin)
        ->get(route('admin.conversations.index'))
        ->assertSee($firstCustomer->customerIdentity->email)
        ->assertDontSee('mensagem secreta do tenant B');

    $this->withCookie('customer_session', $token)
        ->post(route('customer.companies.store'), ['company_id' => Company::factory()->create()->id])
        ->assertSessionHasErrors('company_id');
    $this->withCookie('customer_session', $token)
        ->postJson(route('customer.chat.messages.retry', ['message' => $secondMessage]))
        ->assertNotFound();

    expect($session->fresh()->active_company_id)->toBe($firstCompany->id)
        ->and($firstCustomer->id)->not->toBe($secondCustomer->id)
        ->and($firstConversation->id)->not->toBe($secondConversation->id)
        ->and($firstMessage->company_id)->toBe($firstCompany->id)
        ->and($secondMessage->company_id)->toBe($secondCompany->id)
        ->and($firstAdmin->company_id)->not->toBe($secondAdmin->company_id)
        ->and($firstConnection->company_id)->not->toBe($secondConnection->company_id);
});
