<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(8)),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'country' => 'ML',
            'timezone' => 'Africa/Bamako',
            'locale' => 'fr',
            'currency' => 'XOF',
            'logo_path' => null,
            'status' => 'trial',
            'trial_ends_at' => null,
        ];
    }
}
