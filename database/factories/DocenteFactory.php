<?php

namespace Database\Factories;

use App\Models\Docente;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocenteFactory extends Factory
{
    protected $model = Docente::class;

    public function definition(): array
    {
        return [
            'codEmpleado' => fake()->unique()->numberBetween(1000, 99999),
            'nombreEmpleado' => fake()->firstName(),
            'apellidosEmpleado' => fake()->lastName() . ' ' . fake()->lastName(),
        ];
    }
}