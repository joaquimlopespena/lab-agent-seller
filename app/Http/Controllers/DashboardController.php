<?php

namespace App\Http\Controllers;

use App\Models\AgentConversation;
use App\Models\CompanyCustomer;
use App\Models\CrmConnection;
use App\Models\CrmSyncRun;
use App\Models\PipedrivePipeline;
use App\Tenancy\TenantContext;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(TenantContext $tenantContext): Response
    {
        $company = $tenantContext->company();
        $crmConnection = CrmConnection::query()
            ->forCompany($company)
            ->with('status:id,name,slug')
            ->first(['id', 'company_id', 'status_id', 'provider', 'authorized_at']);

        return Inertia::render('dashboard', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'summary' => [
                'crm_connection' => $crmConnection === null
                    ? null
                    : [
                        'id' => $crmConnection->id,
                        'provider' => $crmConnection->provider,
                        'authorized_at' => $crmConnection->authorized_at?->toISOString(),
                        'status' => [
                            'id' => $crmConnection->status->id,
                            'name' => $crmConnection->status->name,
                            'slug' => $crmConnection->status->slug,
                        ],
                    ],
                'sync_runs_count' => CrmSyncRun::query()->forCompany($company)->count(),
                'synced_records_count' => PipedrivePipeline::query()->forCompany($company)->count(),
                'customers_count' => CompanyCustomer::query()->forCompany($company)->count(),
                'conversations_count' => AgentConversation::query()->forCompany($company)->count(),
            ],
        ]);
    }
}
