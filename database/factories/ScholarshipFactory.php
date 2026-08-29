<?php

namespace Database\Factories;

use App\Models\Scholarship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scholarship>
 */
class ScholarshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->words(3, true),
            'kampus' => fake()->company().' University',
            'kuota' => fake()->numberBetween(5, 50),
            'tingkat_gelar' => 'S1',
            'cakupan' => 'penuh',
            'batas_waktu' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'deskripsi' => fake()->paragraph(),
            'persyaratan' => fake()->paragraph(),
            'status' => 'aktif',
        ];
    }
}
