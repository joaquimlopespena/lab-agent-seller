<?php

use App\Models\Company;
use App\Models\CrmConnection;
use App\Models\CrmConnectionStatus;
use App\Models\CrmSyncStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function column(string $table, string $column): array
{
    return collect(Schema::getColumns($table))
        ->firstWhere('name', $column);
}

function indexColumns(string $table): array
{
    return collect(Schema::getIndexes($table))
        ->map(fn (array $index): array => $index['columns'])
        ->values()
        ->all();
}

function uniqueIndexColumns(string $table): array
{
    return collect(Schema::getIndexes($table))
        ->where('unique', true)
        ->map(fn (array $index): array => $index['columns'])
        ->values()
        ->all();
}

function foreignColumns(string $table): array
{
    return collect(Schema::getForeignKeys($table))
        ->mapWithKeys(fn (array $foreign): array => [$foreign['columns'][0] => $foreign['foreign_table']])
        ->all();
}

function activePipedriveConnection(?Company $company = null): CrmConnection
{
    $company ??= Company::factory()->create();
    $status = CrmConnectionStatus::query()->firstOrCreate(
        ['slug' => CrmConnectionStatus::Active],
        ['name' => 'Active', 'is_active' => true],
    );

    return CrmConnection::factory()->create([
        'company_id' => $company,
        'status_id' => $status,
    ]);
}

function seedSyncStatuses(): void
{
    foreach ([
        CrmSyncStatus::Pending,
        CrmSyncStatus::Running,
        CrmSyncStatus::Succeeded,
        CrmSyncStatus::Failed,
    ] as $slug) {
        CrmSyncStatus::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => Str::headline($slug), 'is_active' => true],
        );
    }
}
