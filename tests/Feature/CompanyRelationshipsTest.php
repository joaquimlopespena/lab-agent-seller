<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('company and its sole administrator navigate in both directions', function () {
    $user = User::factory()->create();

    expect($user->company)->toBeInstanceOf(Company::class)
        ->and($user->company->administrator->is($user))->toBeTrue()
        ->and($user->company_id)->not->toBeNull();
});

test('user factories do not accidentally share a company', function () {
    $users = User::factory()->count(2)->create();

    expect($users->pluck('company_id')->unique())->toHaveCount(2);
});
