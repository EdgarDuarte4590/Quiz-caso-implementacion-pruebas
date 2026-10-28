<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstudianteFactory extends Factory
{
    protected $model = Estudiante::class;

    public function definition(): array
    {
        return [
            'carne' => strtoupper(fake()->unique()->bothify('C#?###')),
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName() . ' ' . fake()->lastName(),
            'docente_id' => Docente::factory(),
        ];
    }
}