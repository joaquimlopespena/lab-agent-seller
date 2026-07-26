<?php

namespace App\Integrations\Pipedrive;

use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;

final class PipedriveOAuthClient implements PipedriveClient
{
    /**
     * @param  array<string, mixed>  $configuration
     */
    public function __construct(
        private Factory $http,
        private array $configuration,
    ) {}

    public function authorizationUrl(string $state): string
    {
        $this->validateConfiguration();

        return $this->configuration['authorization_endpoint'].'?'.http_build_query([
            'client_id' => $this->configuration['client_id'],
            'redirect_uri' => $this->configuration['redirect_uri'],
            'state' => $state,
            'scope' => implode(' ', $this->configuration['scopes']),
        ], encoding_type: PHP_QUERY_RFC3986);
    }

    public function exchangeAuthorizationCode(string $code): PipedriveToken
    {
        $this->validateConfiguration();

        try {
            $response = $this->http
                ->asForm()
                ->withBasicAuth($this->configuration['client_id'], $this->configuration['client_secret'])
                ->connectTimeout((int) $this->configuration['connect_timeout_seconds'])
                ->timeout((int) $this->configuration['timeout_seconds'])
                ->post($this->configuration['token_endpoint'], [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->configuration['redirect_uri'],
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new PipedriveOAuthException('O Pipedrive recusou a troca do código de autorização.', previous: $exception);
        }

        $accessToken = $response->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new PipedriveOAuthException('A resposta OAuth do Pipedrive não contém um token de acesso válido.');
        }

        $expiresIn = $response->json('expires_in');
        $refreshToken = $response->json('refresh_token');
        $externalCompanyId = $response->json('company_id');

        return new PipedriveToken(
            accessToken: $accessToken,
            refreshToken: is_string($refreshToken) ? $refreshToken : null,
            expiresAt: is_numeric($expiresIn) ? new DateTimeImmutable("+{$expiresIn} seconds") : null,
            externalCompanyId: is_scalar($externalCompanyId) ? (string) $externalCompanyId : null,
        );
    }

    private function validateConfiguration(): void
    {
        foreach (['client_id', 'client_secret', 'redirect_uri', 'authorization_endpoint', 'token_endpoint'] as $key) {
            if (! is_string($this->configuration[$key] ?? null) || trim($this->configuration[$key]) === '') {
                throw new PipedriveConfigurationException("A configuração services.pipedrive.{$key} é obrigatória.");
            }
        }

        if (! is_array($this->configuration['scopes'] ?? null) || $this->configuration['scopes'] === []) {
            throw new PipedriveConfigurationException('A configuração services.pipedrive.scopes deve conter ao menos um escopo de leitura.');
        }

        foreach ($this->configuration['scopes'] as $scope) {
            if (! is_string($scope) || ! str_ends_with($scope, ':read')) {
                throw new PipedriveConfigurationException('A configuração services.pipedrive.scopes aceita somente escopos terminados em :read.');
            }
        }
    }
}
