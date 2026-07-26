<?php

namespace App\Integrations\Pipedrive;

use App\Models\Company;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;
use JsonException;

final class PipedriveOAuthState
{
    private const SESSION_KEY = 'pipedrive.oauth';

    public function __construct(private Encrypter $encrypter) {}

    public function create(Session $session, Company $company): string
    {
        $nonce = Str::random(64);
        $expiresAt = now()->addMinutes((int) config('services.pipedrive.state_ttl_minutes', 10))->timestamp;

        $session->put(self::SESSION_KEY, [
            'nonce_hash' => hash('sha256', $nonce),
            'company_id' => $company->getKey(),
            'expires_at' => $expiresAt,
        ]);

        return $this->encrypter->encryptString(json_encode([
            'nonce' => $nonce,
            'company_id' => $company->getKey(),
            'expires_at' => $expiresAt,
        ], JSON_THROW_ON_ERROR));
    }

    public function validateAndConsume(Session $session, Company $company, string $state): bool
    {
        $attempt = $session->pull(self::SESSION_KEY);

        if (! is_array($attempt)) {
            return false;
        }

        try {
            $payload = json_decode($this->encrypter->decryptString($state), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            return false;
        }

        return is_array($payload)
            && is_string($payload['nonce'] ?? null)
            && hash_equals((string) ($attempt['nonce_hash'] ?? ''), hash('sha256', $payload['nonce']))
            && (int) ($attempt['company_id'] ?? 0) === $company->getKey()
            && (int) ($payload['company_id'] ?? 0) === $company->getKey()
            && (int) ($attempt['expires_at'] ?? 0) >= now()->timestamp
            && (int) ($payload['expires_at'] ?? 0) >= now()->timestamp;
    }
}
