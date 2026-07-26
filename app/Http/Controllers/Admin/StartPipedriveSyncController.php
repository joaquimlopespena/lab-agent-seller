<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PipedriveSynchronizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

final class StartPipedriveSyncController extends Controller
{
    public function __construct(private PipedriveSynchronizationService $synchronization) {}

    public function __invoke(Request $request): JsonResponse|RedirectResponse
    {
        $company = $request->user()->company;
        $connection = $company->crmConnection()->firstOrFail();
        try {
            $run = $this->synchronization->synchronize($company, $connection);
        } catch (LogicException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }

            return to_route('admin.integrations.index')->withErrors([
                'synchronization' => $exception->getMessage(),
            ]);
        }

        $payload = [
            'id' => $run->getKey(),
            'status' => $run->status->slug,
        ];

        if ($request->expectsJson()) {
            return response()->json($payload, 201);
        }

        return to_route('admin.integrations.index')
            ->with('sync', $payload);
    }
}
