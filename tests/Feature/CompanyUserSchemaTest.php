<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a company has exactly one required administrator in the MVP', function () {
    $company = Company::factory()->create();
    User::factory()->create(['company_id' => $company->id]);

    expect(column('users', 'company_id')['nullable'])->toBeFalse()
        ->and(uniqueIndexColumns('users'))->toContain(['company_id'])
        ->and(foreignColumns('users'))->toMatchArray(['company_id' => 'companies'])
        ->and(fn () => User::factory()->create(['company_id' => $company->id]))->toThrow(QueryException::class);
});
