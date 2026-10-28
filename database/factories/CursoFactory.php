<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

class CursoFactory extends Factory
{
    protected $model = Curso::class;

    public function definition(): array
    {
        return [
            'sigla' => strtoupper(fake()->unique()->bothify('IF####')),
            'nombreCurso' => fake()->sentence(3),
            'creditos' => fake()->numberBetween(2, 5),
        ];
    }
}