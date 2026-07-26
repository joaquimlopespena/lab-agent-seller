<?php

namespace App\Integrations\Pipedrive;

use DateTimeImmutable;

final readonly class PipedriveToken
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public ?DateTimeImmutable $expiresAt,
        public ?string $externalCompanyId = null,
    ) {}
}
