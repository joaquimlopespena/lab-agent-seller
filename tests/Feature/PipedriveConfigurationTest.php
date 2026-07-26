<?php

use App\Integrations\Pipedrive\PipedriveClient;
use App\Integrations\Pipedrive\PipedriveConfigurationException;
use App\Integrations\Pipedrive\PipedriveToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fails clearly when a client with missing Pipedrive configuration is used', function () {
    config()->set('services.pipedrive.client_id', null);

    expect(fn () => app(PipedriveClient::class)->authorizationUrl('state'))
        ->toThrow(PipedriveConfigurationException::class, 'services.pipedrive.client_id');
});

it('allows the Pipedrive client contract to be replaced by a fake', function () {
    $fake = new class implements PipedriveClient
    {
        public function authorizationUrl(string $state): string
        {
            return 'https://pipedrive.test/authorize?state='.urlencode($state);
        }

        public function exchangeAuthorizationCode(string $code): PipedriveToken
        {
            throw new LogicException('Not needed by this test.');
        }
    };

    app()->instance(PipedriveClient::class, $fake);

    $this->actingAs(User::factory()->create())
        ->get(route('oauth.pipedrive.start'))
        ->assertRedirectContains('https://pipedrive.test/authorize');

    expect(app(PipedriveClient::class))->toBe($fake);
});
