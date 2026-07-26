<?php

namespace Tests;

use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Integrations\Pipedrive\PipedrivePage;
use App\Models\CrmConnection;
use RuntimeException;

final class FakePipedriveDataClient implements PipedriveDataClient
{
    /** @var array<string, list<PipedrivePage>> */
    public array $pages = [];

    /** @var list<string> */
    public array $calls = [];

    /** @var list<string> */
    public array $failures = [];

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function with(string $resource, array $items): self
    {
        $this->pages[$resource] = [new PipedrivePage($items)];

        return $this;
    }

    /**
     * @param  list<PipedrivePage>  $pages
     */
    public function withPages(string $resource, array $pages): self
    {
        $this->pages[$resource] = $pages;

        return $this;
    }

    public function failing(string $resource): self
    {
        $this->failures[] = $resource;

        return $this;
    }

    public function page(CrmConnection $connection, string $resource, ?string $cursor = null): PipedrivePage
    {
        $this->calls[] = "{$connection->company_id}:{$resource}:".($cursor ?? 'first');

        if (in_array($resource, $this->failures, true)) {
            throw new RuntimeException("secret transport failure for {$resource}");
        }

        $index = $cursor === null ? 0 : (int) $cursor;

        return $this->pages[$resource][$index] ?? new PipedrivePage([]);
    }
}
