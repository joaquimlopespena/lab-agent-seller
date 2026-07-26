<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

it('creates and authenticates exactly one company administrator', function () {
    $companyCount = Company::query()->count();
    $userCount = User::query()->count();

    $response = $this->post(route('register.store'), [
        'company_name' => 'Acme Comercial',
        'name' => 'Ana Administradora',
        'email' => 'ana@acme.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $company = Company::query()->latest('id')->firstOrFail();
    $administrator = User::query()->latest('id')->firstOrFail();

    expect($company->name)->toBe('Acme Comercial')
        ->and($administrator->company_id)->toBe($company->id)
        ->and($administrator->name)->toBe('Ana Administradora')
        ->and(Company::query()->count())->toBe($companyCount + 1)
        ->and(User::query()->count())->toBe($userCount + 1);
    $this->assertAuthenticatedAs($administrator);
    $response->assertRedirect(route('dashboard', absolute: false));
});

it('requires every company and administrator registration field', function (string $field) {
    $companyCount = Company::query()->count();
    $userCount = User::query()->count();

    $payload = [
        'company_name' => 'Acme Comercial',
        'name' => 'Ana Administradora',
        'email' => 'ana@acme.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    unset($payload[$field]);

    $this->post(route('register.store'), $payload)
        ->assertSessionHasErrors($field);

    expect(Company::query()->count())->toBe($companyCount)
        ->and(User::query()->count())->toBe($userCount);
})->with([
    'company name' => 'company_name',
    'administrator name' => 'name',
    'email' => 'email',
    'password' => 'password',
]);

it('preserves Fortify email uniqueness and password confirmation validation', function () {
    User::factory()->create(['email' => 'existing@example.test']);
    $companyCount = Company::query()->count();
    $userCount = User::query()->count();

    $this->post(route('register.store'), [
        'company_name' => 'Outra Empresa',
        'name' => 'Outro Admin',
        'email' => 'existing@example.test',
        'password' => 'password',
        'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors(['email', 'password']);

    expect(Company::query()->count())->toBe($companyCount)
        ->and(User::query()->count())->toBe($userCount);
});

it('rolls the company back when administrator persistence fails', function () {
    $companyCount = Company::query()->count();
    $userCount = User::query()->count();

    Event::listen('eloquent.creating: '.User::class, function (): never {
        throw ValidationException::withMessages([
            'email' => 'Não foi possível criar o administrador.',
        ]);
    });

    $this->post(route('register.store'), [
        'company_name' => 'Empresa Transitória',
        'name' => 'Admin Transitório',
        'email' => 'admin@transitoria.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');

    expect(Company::query()->count())->toBe($companyCount)
        ->and(User::query()->count())->toBe($userCount);
});
