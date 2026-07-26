<?php

namespace App\Integrations\Pipedrive;

interface PipedriveClient
{
    public function authorizationUrl(string $state): string;

    public function exchangeAuthorizationCode(string $code): PipedriveToken;
}
