<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Grupo;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrupoFactory extends Factory
{
    protected $model = Grupo::class;

    public function definition(): array
    {
        return [
            'numeroCiclo' => fake()->numberBetween(1, 3),
            'ano' => 2026,
            'numeroGrupo' => fake()->numberBetween(1, 5),
            'abierto' => true,
            'curso_id' => Curso::factory(),
        ];
    }
}