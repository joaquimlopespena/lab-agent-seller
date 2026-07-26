<?php

namespace App\CustomerAuth;

use App\Models\CustomerIdentity;
use App\Models\CustomerMagicLink;
use Illuminate\Support\Str;

final class IssueCustomerMagicLink
{
    public function __construct(
        private CustomerAuthConfiguration $configuration,
        private CustomerMagicLinkSender $sender,
    ) {}

    public function handle(string $email): bool
    {
        $normalizedEmail = Str::of($email)->trim()->lower()->toString();
        $identity = CustomerIdentity::query()
            ->where('email', $normalizedEmail)
            ->whereHas('companyCustomers')
            ->first();

        if ($identity === null) {
            return false;
        }

        $token = Str::random(64);
        $expiresAt = now()->addMinutes($this->configuration->magicLinkTtlMinutes);

        CustomerMagicLink::query()->create([
            'customer_identity_id' => $identity->getKey(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
        ]);

        $this->sender->send($normalizedEmail, $token, $expiresAt);

        return true;
    }
}
