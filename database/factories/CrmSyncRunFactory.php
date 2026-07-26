<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CrmConnection;
use App\Models\CrmSyncRun;
use App\Models\CrmSyncStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CrmSyncRun> */
class CrmSyncRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'crm_connection_id' => fn (array $attributes) => CrmConnection::factory()->create(['company_id' => $attributes['company_id']]),
            'status_id' => CrmSyncStatus::factory(),
            'started_at' => now(),
            'finished_at' => null,
            'error_message' => null,
        ];
    }
}
