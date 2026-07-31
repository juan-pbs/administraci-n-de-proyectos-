<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'coordinacion',
            'docente_lider',
            'docente_materia',
            'estudiante',
        ]).'-'.fake()->unique()->numberBetween(100, 999);

        return [
            'nombre' => $name,
            'nombre_visible' => str($name)->replace('-', ' ')->title()->toString(),
            'descripcion' => fake()->sentence(),
        ];
    }
}
