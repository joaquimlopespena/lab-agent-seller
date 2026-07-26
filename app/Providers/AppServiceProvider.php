<?php

namespace App\Providers;

use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\CompanyCustomer;
use App\Models\CrmConnection;
use App\Models\CrmSyncRun;
use App\Models\PipedriveActivity;
use App\Models\PipedriveCustomField;
use App\Models\PipedriveDeal;
use App\Models\PipedriveOrganization;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePersonEmail;
use App\Models\PipedrivePipeline;
use App\Models\PipedriveProduct;
use App\Models\PipedriveStage;
use App\Models\PipedriveUser;
use App\Policies\TenantOwnedPolicy;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerTenantPolicies();
        $this->configureDefaults();
    }

    private function registerTenantPolicies(): void
    {
        foreach ([
            AgentConversation::class,
            AgentConversationMessage::class,
            CompanyCustomer::class,
            CrmConnection::class,
            CrmSyncRun::class,
            PipedriveActivity::class,
            PipedriveCustomField::class,
            PipedriveDeal::class,
            PipedriveOrganization::class,
            PipedrivePerson::class,
            PipedrivePersonEmail::class,
            PipedrivePipeline::class,
            PipedriveProduct::class,
            PipedriveStage::class,
            PipedriveUser::class,
        ] as $tenantOwnedModel) {
            Gate::policy($tenantOwnedModel, TenantOwnedPolicy::class);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
