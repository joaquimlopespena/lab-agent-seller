<?php

namespace App\Providers;

use App\CustomerAuth\CustomerAuthConfiguration;
use App\CustomerAuth\CustomerMagicLinkSender;
use App\CustomerAuth\LaravelNotificationMagicLinkSender;
use App\Integrations\Pipedrive\HttpPipedriveDataClient;
use App\Integrations\Pipedrive\PipedriveClient;
use App\Integrations\Pipedrive\PipedriveDataClient;
use App\Integrations\Pipedrive\PipedriveOAuthClient;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\CompanyCustomer;
use App\Models\CrmConnection;
use App\Models\CrmSyncRun;
use App\Models\CustomerSession;
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
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Auth;
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
        $this->app->singleton(
            CustomerAuthConfiguration::class,
            fn (): CustomerAuthConfiguration => new CustomerAuthConfiguration(config('customer_auth')),
        );
        $this->app->bind(CustomerMagicLinkSender::class, LaravelNotificationMagicLinkSender::class);
        $this->app->bind(PipedriveClient::class, fn ($app): PipedriveClient => new PipedriveOAuthClient(
            $app->make(HttpFactory::class),
            config('services.pipedrive'),
        ));
        $this->app->bind(PipedriveDataClient::class, fn ($app): PipedriveDataClient => new HttpPipedriveDataClient(
            $app->make(HttpFactory::class),
            config('services.pipedrive'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::viaRequest('customer-session', function ($request) {
            $configuration = app(CustomerAuthConfiguration::class);
            $token = $request->cookie($configuration->cookieName);

            if (! is_string($token) || $token === '') {
                return null;
            }

            $session = CustomerSession::query()
                ->with('customerIdentity')
                ->where('token_hash', hash('sha256', $token))
                ->first();

            if ($session === null || ! $session->isValid()) {
                return null;
            }

            $session->update(['last_activity_at' => now()]);
            $request->attributes->set('customerSession', $session);

            return $session->customerIdentity;
        });

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
