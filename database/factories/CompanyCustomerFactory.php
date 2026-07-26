<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyCustomer;
use App\Models\CustomerIdentity;
use App\Models\PipedrivePerson;
use App\Models\PipedrivePersonEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompanyCustomer> */
class CompanyCustomerFactory extends Factory
{
    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'company_id' => Company::factory(),
            'customer_identity_id' => CustomerIdentity::factory()->state(['email' => $email]),
            'pipedrive_person_id' => fn (array $attributes) => PipedrivePerson::factory()->create([
                'company_id' => $attributes['company_id'],
            ]),
            'pipedrive_person_email_id' => fn (array $attributes) => PipedrivePersonEmail::factory()->primary()->create([
                'company_id' => $attributes['company_id'],
                'pipedrive_person_id' => $attributes['pipedrive_person_id'],
                'email' => $email,
            ]),
        ];
    }
}
