<?php

use App\Models\Company;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware('web')->get('/_test/tenant-context', function (TenantContext $tenantContext) {
        return response()->json([
            'company_id' => $tenantContext->current()?->id,
            'request_company_id' => request()->attributes->get('currentCompany')?->id,
        ]);
    });
});

it('resolves the company exclusively from the authenticated administrator', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $administrator = User::factory()->for($company)->create();

    $this->actingAs($administrator)
        ->get('/_test/tenant-context?company_id='.$otherCompany->id)
        ->assertOk()
        ->assertJson([
            'company_id' => $company->id,
            'request_company_id' => $company->id,
        ]);
});

it('shares only non-sensitive identification for the authenticated company', function () {
    $company = Company::factory()->create();
    $administrator = User::factory()->for($company)->create();

    $this->actingAs($administrator)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.company', [
                'id' => $company->id,
                'name' => $company->name,
            ])
            ->missing('auth.company.created_at')
            ->missing('auth.company.updated_at'));
});

it('has no tenant context for a visitor', function () {
    $this->get('/_test/tenant-context')
        ->assertOk()
        ->assertJson([
            'company_id' => null,
            'request_company_id' => null,
        ]);
});
