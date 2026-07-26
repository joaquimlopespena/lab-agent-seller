<?php

namespace Database\Factories;

use App\Models\CrmSyncStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CrmSyncStatus> */
class CrmSyncStatusFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word();

        return ['name' => Str::headline($name), 'slug' => Str::slug($name), 'description' => fake()->sentence(), 'is_active' => true];
    }
}
