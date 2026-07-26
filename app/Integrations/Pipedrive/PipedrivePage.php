<?php

namespace App\Integrations\Pipedrive;

final readonly class PipedrivePage
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor = null,
    ) {}
}
