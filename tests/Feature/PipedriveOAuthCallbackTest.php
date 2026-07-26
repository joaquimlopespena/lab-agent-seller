<?php

use App\Integrations\Pipedrive\PipedriveClient;
use App\Integrations\Pipedrive\PipedriveToken;
use App\Models\Company;
use App\Models\CrmConnection;
use App\Models\CrmConnectionStatus;
use App\Models\User;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);

    $this->pipedrive = new class implements PipedriveClient
    {
        public array $exchangedCodes = [];

        public PipedriveToken $token;

        public function __construct()
        {
            $this->token = new PipedriveToken(
                accessToken: 'access-secret',
                refreshToken: 'refresh-secret',
                expiresAt: now()->addHour()->toDateTimeImmutable(),
                externalCompanyId: 'pipedrive-company-10',
            );
        }

        public function authorizationUrl(string $state): string
        {
            return 'https://pipedrive.test/authorize?state='.urlencode($state);
        }

        public function exchangeAuthorizationCode(string $code): PipedriveToken
        {
            $this->exchangedCodes[] = $code;

            return $this->token;
        }
    };

    app()->instance(PipedriveClient::class, $this->pipedrive);
});

function beginPipedriveOAuth(object $test, User $administrator): string
{
    $location = $test->actingAs($administrator)
        ->get(route('oauth.pipedrive.start'))
        ->headers
        ->get('Location');

    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    return $query['state'];
}

it('exchanges a valid code and stores an active encrypted tenant connection', function () {
    $administrator = User::factory()->create();
    $state = beginPipedriveOAuth($this, $administrator);

    $this->get(route('oauth.pipedrive.callback', ['state' => $state, 'code' => 'valid-code']))
        ->assertRedirect(route('admin.integrations.index'))
        ->assertSessionHas('toast.type', 'success');

    $connection = CrmConnection::query()->whereBelongsTo($administrator->company)->firstOrFail();
    $rawTokens = DB::table((new CrmConnection)->getTable())
        ->where('id', $connection->getKey())
        ->first(['access_token', 'refresh_token']);

    expect($this->pipedrive->exchangedCodes)->toBe(['valid-code'])
        ->and($connection->status->slug)->toBe(CrmConnectionStatus::Active)
        ->and($connection->external_company_id)->toBe('pipedrive-company-10')
        ->and($connection->access_token)->toBe('access-secret')
        ->and($connection->refresh_token)->toBe('refresh-secret')
        ->and($connection->authorized_at)->not->toBeNull()
        ->and($rawTokens->access_token)->not->toBe('access-secret')
        ->and($rawTokens->refresh_token)->not->toBe('refresh-secret');
});

it('reconnects by updating the existing connection without creating another', function () {
    $administrator = User::factory()->create();
    $connection = CrmConnection::factory()->for($administrator->company)->create();
    $originalAuthorization = $connection->authorized_at;
    $this->travel(2)->minutes();
    $state = beginPipedriveOAuth($this, $administrator);
    $this->pipedrive->token = new PipedriveToken('new-access', 'new-refresh', null, 'new-company');

    $this->get(route('oauth.pipedrive.callback', ['state' => $state, 'code' => 'reconnect']))
        ->assertRedirect(route('admin.integrations.index'));

    expect(CrmConnection::query()->whereBelongsTo($administrator->company)->count())->toBe(1)
        ->and($connection->fresh()->access_token)->toBe('new-access')
        ->and($connection->fresh()->authorized_at->greaterThan($originalAuthorization))->toBeTrue();
});

it('does not activate a connection after denial, invalid state, expiration, or missing code', function (string $scenario) {
    $administrator = User::factory()->create();
    $state = beginPipedriveOAuth($this, $administrator);
    $query = ['state' => $state];

    if ($scenario === 'denied') {
        $query['error'] = 'access_denied';
    } elseif ($scenario === 'invalid state') {
        $query = ['state' => 'tampered-state', 'code' => 'code'];
    } elseif ($scenario === 'expired') {
        $this->travel(11)->minutes();
        $query['code'] = 'code';
    }

    $this->get(route('oauth.pipedrive.callback', $query))
        ->assertRedirect(route('admin.integrations.index'))
        ->assertSessionHas('toast.type', 'error');

    expect(CrmConnection::query()->whereBelongsTo($administrator->company)->exists())->toBeFalse();
})->with(['denied', 'invalid state', 'expired', 'missing code']);

it('cannot use one tenant OAuth state to create or update another tenant connection', function () {
    $firstCompany = Company::factory()->create();
    $secondCompany = Company::factory()->create();
    $firstAdministrator = User::factory()->for($firstCompany)->create();
    $secondAdministrator = User::factory()->for($secondCompany)->create();
    $secondConnection = CrmConnection::factory()->for($secondCompany)->create();
    $state = beginPipedriveOAuth($this, $firstAdministrator);

    $this->actingAs($secondAdministrator)
        ->get(route('oauth.pipedrive.callback', ['state' => $state, 'code' => 'stolen-code']))
        ->assertRedirect(route('admin.integrations.index'))
        ->assertSessionHas('toast.type', 'error');

    expect(CrmConnection::query()->whereBelongsTo($firstCompany)->exists())->toBeFalse()
        ->and($secondConnection->fresh()->access_token)->toBe($secondConnection->access_token)
        ->and($this->pipedrive->exchangedCodes)->toBe([]);
});
