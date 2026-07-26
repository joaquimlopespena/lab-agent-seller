<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmConnectionStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PipedriveConnectionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $connection = $request->user()->company->crmConnection()
            ->with([
                'status:id,name,slug',
                'syncRuns' => fn ($query) => $query
                    ->with('status:id,name,slug')
                    ->latest()
                    ->limit(25),
            ])
            ->first();

        return Inertia::render('admin/integrations/index', [
            'connection' => $connection === null ? null : [
                'id' => $connection->getKey(),
                'provider' => $connection->provider,
                'status' => $connection->status->only(['id', 'name', 'slug']),
                'authorized_at' => $connection->authorized_at?->toISOString(),
                'token_expires_at' => $connection->token_expires_at?->toISOString(),
                'last_error' => $connection->last_error,
            ],
            'displayStatus' => $connection?->status->slug ?? 'disconnected',
            'canConnect' => $connection === null || in_array($connection->status->slug, [
                CrmConnectionStatus::Failed,
                CrmConnectionStatus::Revoked,
                CrmConnectionStatus::Expired,
            ], true),
            'canReconnect' => $connection !== null,
            'canSynchronize' => $connection?->status->slug === CrmConnectionStatus::Active
                && ! $connection->syncRuns->contains(fn ($run): bool => $run->status->slug === 'running'),
            'syncRuns' => $connection?->syncRuns->map(fn ($run): array => [
                'id' => $run->getKey(),
                'status' => $run->status->only(['id', 'name', 'slug']),
                'started_at' => $run->started_at?->toISOString(),
                'finished_at' => $run->finished_at?->toISOString(),
                'error_message' => $run->error_message,
                'created_at' => $run->created_at->toISOString(),
            ])->values() ?? [],
        ]);
    }
}
