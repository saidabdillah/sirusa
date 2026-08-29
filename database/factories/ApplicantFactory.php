<?php

namespace Database\Factories;

use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Applicant>
 */
class ApplicantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'beasiswa_id' => Scholarship::factory(),
            'fakultas' => fake()->words(2, true),
            'prodi' => fake()->words(2, true),
            'ipk' => fake()->randomFloat(2, 2.5, 4.0),
            'semester' => fake()->numberBetween(1, 8),
            'status' => 'verifikasi',
            'catatan' => null,
        ];
    }
}
