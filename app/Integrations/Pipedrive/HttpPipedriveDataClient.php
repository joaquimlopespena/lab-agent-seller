<?php

namespace App\Integrations\Pipedrive;

use App\Models\CrmConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Throwable;

final class HttpPipedriveDataClient implements PipedriveDataClient
{
    /**
     * @param  array<string, mixed>  $configuration
     */
    public function __construct(
        private Factory $http,
        private array $configuration,
    ) {}

    public function page(CrmConnection $connection, string $resource, ?string $cursor = null): PipedrivePage
    {
        $baseUrl = rtrim((string) ($this->configuration['api_endpoint'] ?? 'https://api.pipedrive.com/v1'), '/');
        $query = ['limit' => (int) ($this->configuration['page_size'] ?? 100)];

        if ($cursor !== null) {
            $query[ctype_digit($cursor) ? 'start' : 'cursor'] = $cursor;
        }

        try {
            $response = $this->http
                ->baseUrl($baseUrl)
                ->acceptJson()
                ->withToken($connection->access_token)
                ->connectTimeout((int) ($this->configuration['connect_timeout_seconds'] ?? 3))
                ->timeout((int) ($this->configuration['timeout_seconds'] ?? 10))
                ->retry(
                    (int) ($this->configuration['sync_attempts'] ?? 3),
                    (int) ($this->configuration['sync_retry_delay_milliseconds'] ?? 100),
                    fn (Throwable $exception, PendingRequest $request): bool => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429)),
                )
                ->get('/'.ltrim($resource, '/'), $query)
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new PipedriveTransportException('Não foi possível consultar os dados do Pipedrive.', previous: $exception);
        }

        $items = $response->json('data', []);

        if (! is_array($items)) {
            throw new PipedriveTransportException('O Pipedrive retornou uma coleção inválida.');
        }

        $nextCursor = $response->json('additional_data.next_cursor')
            ?? $response->json('additional_data.pagination.next_start');
        $hasMore = $response->json('additional_data.pagination.more_items_in_collection');

        if ($hasMore === false) {
            $nextCursor = null;
        }

        return new PipedrivePage(
            items: array_values(array_filter($items, 'is_array')),
            nextCursor: is_scalar($nextCursor) ? (string) $nextCursor : null,
        );
    }
}
