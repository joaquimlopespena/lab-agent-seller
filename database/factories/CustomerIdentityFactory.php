<?php

namespace Database\Factories;

use App\Models\CustomerIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomerIdentity> */
class CustomerIdentityFactory extends Factory
{
    public function definition(): array
    {
        return ['email' => fake()->unique()->safeEmail()];
    }
}
