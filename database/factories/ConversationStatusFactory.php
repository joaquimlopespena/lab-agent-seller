<?php

namespace Database\Factories;

use App\Models\ConversationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ConversationStatus> */
class ConversationStatusFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => Str::headline($name), 'slug' => Str::slug($name), 'description' => fake()->sentence(), 'is_active' => true];
    }
}
