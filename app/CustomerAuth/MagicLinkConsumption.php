<?php

namespace App\CustomerAuth;

use App\Models\CustomerSession;

final readonly class MagicLinkConsumption
{
    private function __construct(
        public ?CustomerSession $session,
        public ?string $token,
        public string $failure,
    ) {}

    public static function succeeded(CustomerSession $session, string $token): self
    {
        return new self($session, $token, '');
    }

    public static function failed(string $failure): self
    {
        return new self(null, null, $failure);
    }

    public function successful(): bool
    {
        return $this->session !== null && $this->token !== null;
    }
}
