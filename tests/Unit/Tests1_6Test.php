<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// CP-01
test('Registrar un grupo con toda su información y su curso', function () {
    $curso = Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombreCurso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);

    $grupoCreado = Grupo::factory()->create([
        'numeroGrupo' => 1,
        'ano' => 2026,
        'numeroCiclo' => 2,
        'abierto' => true,
        'curso_id' => $curso->id,
    ]);

    $grupo = Grupo::find($grupoCreado->id);

    expect($grupo->numeroGrupo)->toBe(1);
    expect($grupo->ano)->toBe(2026);
    expect($grupo->numeroCiclo)->toBe(2);
    expect($grupo->abierto)->toBeTrue();
    expect($grupo->curso_id)->toBe($curso->id);
    expect($grupo->curso->sigla)->toBe('IF6100');
    expect($grupo->curso->creditos)->toBe(4);
});

// CP-02
test('Un curso tiene varios grupos y se distinguen los abiertos', function () {
    $curso = Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombreCurso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);

    $grupo1 = Grupo::factory()->create([
        'numeroGrupo' => 1,
        'ano' => 2026,
        'numeroCiclo' => 2,
        'abierto' => true,
        'curso_id' => $curso->id,
    ]);

    $grupo2 = Grupo::factory()->create([
        'numeroGrupo' => 2,
        'ano' => 2026,
        'numeroCiclo' => 2,
        'abierto' => true,
        'curso_id' => $curso->id,
    ]);

    $grupo3 = Grupo::factory()->create([
        'numeroGrupo' => 3,
        'ano' => 2026,
        'numeroCiclo' => 2,
        'abierto' => false,
        'curso_id' => $curso->id,
    ]);

    $gruposAbiertos = $curso->listarGruposAbiertos();

    expect($curso->grupos)->toHaveCount(3);
    expect($gruposAbiertos)->toHaveCount(2);
    expect($grupo1->curso_id)->toBe($curso->id);
    expect($grupo2->curso_id)->toBe($curso->id);
    expect($grupo3->curso_id)->toBe($curso->id);
});

// CP-03
test('Un grupo acepta dos docentes', function () {
    $curso = Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombreCurso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);

    $grupo = Grupo::factory()->create([
        'numeroCiclo' => 2,
        'ano' => 2026,
        'numeroGrupo' => 1,
        'abierto' => false,
        'curso_id' => $curso->id,
    ]);

    $docente1001 = Docente::factory()->create([
        'codEmpleado' => 1001,
        'nombreEmpleado' => 'Ana',
        'apellidosEmpleado' => 'Mora Vargas',
    ]);

    $docente1002 = Docente::factory()->create([
        'codEmpleado' => 1002,
        'nombreEmpleado' => 'Luis',
        'apellidosEmpleado' => 'Rojas Solano',
    ]);

    $grupo->asignarDocente(1001);
    $grupo->asignarDocente(1002);

    expect($grupo->docentes)->toHaveCount(2);
    expect($grupo->docentes->pluck('codEmpleado')->toArray())->toEqual([1001, 1002]);
    expect($docente1001->grupos()->where('grupos.id', $grupo->id)->exists())->toBeTrue();
});

// CP-04
test('Un grupo rechaza un tercer docente', function () {
    $curso = Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombreCurso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);

    $grupo = Grupo::factory()->create([
        'numeroCiclo' => 2,
        'ano' => 2026,
        'numeroGrupo' => 1,
        'abierto' => false,
        'curso_id' => $curso->id,
    ]);

    Docente::factory()->create([
        'codEmpleado' => 1001,
        'nombreEmpleado' => 'Ana',
        'apellidosEmpleado' => 'Mora Vargas',
    ]);

    Docente::factory()->create([
        'codEmpleado' => 1002,
        'nombreEmpleado' => 'Luis',
        'apellidosEmpleado' => 'Rojas Solano',
    ]);

    Docente::factory()->create([
        'codEmpleado' => 1003,
        'nombreEmpleado' => 'Carla',
        'apellidosEmpleado' => 'Jiménez Araya',
    ]);

    $grupo->asignarDocente(1001);
    $grupo->asignarDocente(1002);

    expect(fn () => $grupo->asignarDocente(1003))
        ->toThrow(DomainException::class);

    expect($grupo->docentes()->count())->toBe(2);
    expect($grupo->docentes()->where('codEmpleado', 1003)->exists())->toBeFalse();
});

// CP-05
test('Un grupo con 14 estudiantes no puede abrirse', function () {
    $curso = Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombreCurso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);

    $grupo = Grupo::factory()->create([
        'numeroCiclo' => 2,
        'ano' => 2026,
        'numeroGrupo' => 1,
        'abierto' => false,
        'curso_id' => $curso->id,
    ]);

    $docente1001 = Docente::factory()->create([
        'codEmpleado' => 1001,
        'nombreEmpleado' => 'Ana',
        'apellidosEmpleado' => 'Mora Vargas',
    ]);

    $grupo->asignarDocente(1001);

    $estudiantes = Estudiante::factory()->count(14)->create([
        'docente_id' => $docente1001->id,
    ]);

    $grupo->estudiantes()->attach($estudiantes->pluck('id'), ['nota' => null]);

    expect($grupo->puedeAbrirse())->toBeFalse();
    expect(fn () => $grupo->abrir())->toThrow(DomainException::class);
    expect($grupo->fresh()->abierto)->toBeFalse();
});

// CP-06
test('Un grupo con 15 estudiantes y un docente se abre', function () {
    $curso = Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombreCurso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);

    $grupo = Grupo::factory()->create([
        'numeroCiclo' => 2,
        'ano' => 2026,
        'numeroGrupo' => 1,
        'abierto' => false,
        'curso_id' => $curso->id,
    ]);

    $docente1001 = Docente::factory()->create([
        'codEmpleado' => 1001,
        'nombreEmpleado' => 'Ana',
        'apellidosEmpleado' => 'Mora Vargas',
    ]);

    $grupo->asignarDocente(1001);

    $estudiantes = Estudiante::factory()->count(15)->create([
        'docente_id' => $docente1001->id,
    ]);

    $grupo->estudiantes()->attach($estudiantes->pluck('id'), ['nota' => null]);

    expect($grupo->puedeAbrirse())->toBeTrue();

    $grupo->abrir();

    expect($grupo->fresh()->abierto)->toBeTrue();
});