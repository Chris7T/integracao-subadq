<?php

namespace Database\Factories;

use App\Enums\SubacquirerTypeEnum;
use App\Models\Subacquirer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subacquirer>
 */
class SubacquirerFactory extends Factory
{
    protected $model = Subacquirer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['SubadqA', 'SubadqB']),
            'base_url' => fake()->url(),
            'active' => true,
        ];
    }
}

