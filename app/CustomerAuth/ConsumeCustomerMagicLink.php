<?php

namespace App\CustomerAuth;

use App\Models\CustomerMagicLink;
use App\Models\CustomerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ConsumeCustomerMagicLink
{
    public function __construct(private CustomerAuthConfiguration $configuration) {}

    public function handle(string $token, Request $request): MagicLinkConsumption
    {
        return DB::transaction(function () use ($token, $request): MagicLinkConsumption {
            $magicLink = CustomerMagicLink::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if ($magicLink === null) {
                return MagicLinkConsumption::failed('invalid');
            }

            if ($magicLink->used_at !== null) {
                return MagicLinkConsumption::failed('used');
            }

            if ($magicLink->expires_at->isPast()) {
                return MagicLinkConsumption::failed('expired');
            }

            $sessionToken = Str::random(64);
            $now = now();
            $session = CustomerSession::query()->create([
                'id' => (string) Str::uuid7(),
                'customer_identity_id' => $magicLink->customer_identity_id,
                'active_company_id' => null,
                'token_hash' => hash('sha256', $sessionToken),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 65535, ''),
                'expires_at' => $now->addHours($this->configuration->sessionTtlHours),
                'last_activity_at' => $now,
            ]);

            $magicLink->update(['used_at' => $now]);

            return MagicLinkConsumption::succeeded($session, $sessionToken);
        }, 3);
    }
}
