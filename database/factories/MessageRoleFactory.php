<?php

namespace Database\Factories;

use App\Models\MessageRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MessageRole> */
class MessageRoleFactory extends Factory
{
    public function definition(): array
    {
        $role = fake()->unique()->randomElement(['user', 'assistant', 'system']);

        return ['name' => ucfirst($role), 'slug' => $role, 'description' => fake()->sentence(), 'is_active' => true];
    }
}
