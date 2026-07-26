<?php

namespace App\Integrations\Pipedrive;

use App\Models\CrmConnection;

interface PipedriveDataClient
{
    public function page(CrmConnection $connection, string $resource, ?string $cursor = null): PipedrivePage;
}
