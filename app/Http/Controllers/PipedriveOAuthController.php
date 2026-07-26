<?php

namespace App\Http\Controllers;

use App\Integrations\Pipedrive\PipedriveClient;
use App\Integrations\Pipedrive\PipedriveOAuthException;
use App\Integrations\Pipedrive\PipedriveOAuthState;
use App\Models\CrmConnection;
use App\Models\CrmConnectionStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PipedriveOAuthController extends Controller
{
    public function __construct(
        private PipedriveClient $pipedrive,
        private PipedriveOAuthState $state,
    ) {}

    public function start(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        $state = $this->state->create($request->session(), $company);

        return redirect()->away($this->pipedrive->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        $state = $request->string('state')->toString();

        if ($state === '' || ! $this->state->validateAndConsume($request->session(), $company, $state)) {
            return $this->failure('A tentativa de conexão expirou ou não pertence a esta sessão.');
        }

        if ($request->filled('error')) {
            $this->markExistingConnectionAsFailed($company->crmConnection, 'Autorização negada no Pipedrive.');

            return $this->failure('A autorização do Pipedrive foi cancelada.');
        }

        $code = $request->string('code')->toString();

        if ($code === '') {
            $this->markExistingConnectionAsFailed($company->crmConnection, 'Código OAuth ausente.');

            return $this->failure('O Pipedrive não retornou um código de autorização válido.');
        }

        try {
            $token = $this->pipedrive->exchangeAuthorizationCode($code);
        } catch (PipedriveOAuthException $exception) {
            $this->markExistingConnectionAsFailed($company->crmConnection, $exception->getMessage());

            return $this->failure('Não foi possível concluir a autorização do Pipedrive.');
        }

        CrmConnection::query()->updateOrCreate(
            ['company_id' => $company->getKey()],
            [
                'status_id' => $this->statusId(CrmConnectionStatus::Active),
                'provider' => 'pipedrive',
                'external_company_id' => $token->externalCompanyId,
                'access_token' => $token->accessToken,
                'refresh_token' => $token->refreshToken,
                'token_expires_at' => $token->expiresAt,
                'authorized_at' => now(),
                'last_error' => null,
            ],
        );

        return redirect()
            ->route('admin.integrations.index')
            ->with('toast', ['type' => 'success', 'message' => 'Pipedrive conectado com sucesso.']);
    }

    private function markExistingConnectionAsFailed(?CrmConnection $connection, string $error): void
    {
        $connection?->update([
            'status_id' => $this->statusId(CrmConnectionStatus::Failed),
            'last_error' => $error,
        ]);
    }

    private function statusId(string $slug): int
    {
        return CrmConnectionStatus::query()->where('slug', $slug)->firstOrFail()->getKey();
    }

    private function failure(string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.integrations.index')
            ->with('toast', ['type' => 'error', 'message' => $message]);
    }
}
