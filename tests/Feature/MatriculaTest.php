<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

mutates(Grupo::class);

// =========================================================================
// CP-01: Registrar un grupo con toda su información y su curso
// =========================================================================
test('CP-01 Registrar un grupo con toda su información y su curso', function () {
    $curso = Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombreCurso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);

    $grupoCreado = Grupo::factory()->create([
        'numeroCiclo' => 2,
        'ano' => 2026,
        'numeroGrupo' => 1,
        'abierto' => true,
        'curso_id' => $curso->id,
    ]);

    $grupo = Grupo::find($grupoCreado->id);

    // Resultado esperado
    expect($grupo->numeroCiclo)->toBe(2)
        ->and($grupo->ano)->toBe(2026)
        ->and($grupo->numeroGrupo)->toBe(1)
        ->and($grupo->abierto)->toBeTrue()
        ->and($grupo->abierto)->toBeBool()
        ->and($grupo->curso->sigla)->toBe('IF6100')
        ->and($grupo->curso->creditos)->toBe(4);

    // Poscondición: Tabla grupos con 1 registro
    expect(Grupo::count())->toBe(1);
});

// =========================================================================
// CP-02: Un curso tiene varios grupos y se distinguen los abiertos
// =========================================================================
test('CP-02 Un curso tiene varios grupos y se distinguen los abiertos', function () {
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

    $todosLosGrupos = $curso->grupos;
    $gruposAbiertos = $todosLosGrupos->where('abierto', true);

    // Resultado esperado: $curso->grupos cuenta 3. Los abiertos son 2: los grupos 1 y 2
    expect($todosLosGrupos)->toHaveCount(3)
        ->and($gruposAbiertos)->toHaveCount(2)
        ->and($gruposAbiertos->pluck('numeroGrupo')->values()->all())->toEqual([1, 2]);

    // Poscondición: Los 3 grupos apuntan al mismo curso_id
    expect($grupo1->fresh()->curso_id)->toBe($curso->id)
        ->and($grupo2->fresh()->curso_id)->toBe($curso->id)
        ->and($grupo3->fresh()->curso_id)->toBe($curso->id);
});

// =========================================================================
// CP-03: Un grupo acepta dos docentes
// =========================================================================
test('CP-03 Un grupo acepta dos docentes', function () {
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

    $grupo->asignarDocente($docente1001);
    $grupo->asignarDocente($docente1002);

    // Resultado esperado
    expect($grupo->docentes)->toHaveCount(2)
        ->and($grupo->docentes->pluck('codEmpleado')->all())->toEqual([1001, 1002])
        ->and($docente1001->grupos()->where('grupos.id', $grupo->id)->exists())->toBeTrue();

    // Poscondición: 2 registros en grupo_docente
    expect(DB::table('grupo_docente')->count())->toBe(2);
});

// =========================================================================
// CP-04: Un grupo rechaza un tercer docente
// =========================================================================
test('CP-04 Un grupo rechaza un tercer docente', function () {
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

    $docente1003 = Docente::factory()->create([
        'codEmpleado' => 1003,
        'nombreEmpleado' => 'Carla',
        'apellidosEmpleado' => 'Jiménez Araya',
    ]);

    $grupo->asignarDocente($docente1001);
    $grupo->asignarDocente($docente1002);

    // Resultado esperado: Se lanza DomainException
    expect(fn () => $grupo->asignarDocente($docente1003))
        ->toThrow(DomainException::class);

    // Poscondición: El grupo sigue con 2 docentes. 1003 no aparece en grupo_docente
    expect($grupo->docentes()->count())->toBe(2)
        ->and(DB::table('grupo_docente')->where('docente_id', $docente1003->id)->exists())->toBeFalse();
});

// =========================================================================
// CP-05: Un grupo con 14 estudiantes no puede abrirse
// =========================================================================
test('CP-05 Un grupo con 14 estudiantes no puede abrirse', function () {
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

    $grupo->asignarDocente($docente1001);

    $estudiantes = Estudiante::factory()->count(14)->create([
        'consejero_id' => $docente1001->id,
    ]);

    $grupo->estudiantes()->attach($estudiantes->pluck('id'));

    // Resultado esperado
    expect($grupo->puedeAbrirse())->toBeFalse();
    expect(fn () => $grupo->abrir())->toThrow(DomainException::class);

    // Poscondición: En la base de datos el grupo sigue con abierto = false
    expect($grupo->fresh()->abierto)->toBeFalse();
});

// =========================================================================
// CP-06: Un grupo con 15 estudiantes y un docente se abre
// =========================================================================
test('CP-06 Un grupo con 15 estudiantes y un docente se abre', function () {
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

    $grupo->asignarDocente($docente1001);

    $estudiantes = Estudiante::factory()->count(15)->create([
        'consejero_id' => $docente1001->id,
    ]);

    $grupo->estudiantes()->attach($estudiantes->pluck('id'));

    // Resultado esperado
    expect($grupo->puedeAbrirse())->toBeTrue();
    $grupo->abrir();

    // Poscondición: En la base de datos el grupo queda con abierto = true
    expect($grupo->fresh()->abierto)->toBeTrue();
});

// =========================================================================
// CP-07: Un grupo sin docente no se abre aunque tenga cupo completo
// =========================================================================
test('CP-07 Un grupo sin docente no se abre aunque tenga cupo completo', function () {
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

    $estudiantes = Estudiante::factory()->count(20)->create();
    $grupo->estudiantes()->attach($estudiantes->pluck('id'));

    // Resultado esperado: puedeAbrirse() devuelve false. abrir() lanza DomainException
    expect($grupo->puedeAbrirse())->toBeFalse();
    expect(fn () => $grupo->abrir())->toThrow(DomainException::class);

    // Poscondición: El grupo sigue con abierto = false
    expect($grupo->fresh()->abierto)->toBeFalse();
});

// =========================================================================
// CP-08: Un estudiante queda ligado a su profesor consejero
// =========================================================================
test('CP-08 Un estudiante queda ligado a su profesor consejero', function () {
    $docente1001 = Docente::factory()->create([
        'codEmpleado' => 1001,
        'nombreEmpleado' => 'Ana',
        'apellidosEmpleado' => 'Mora Vargas',
    ]);

    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $docente1001->id,
    ]);

    // Resultado esperado: El consejero de C12345 tiene codEmpleado 1001.
    // El docente 1001 lista exactamente 1 estudiante: C12345
    expect($estudiante->consejero->codEmpleado)->toBe(1001)
        ->and($docente1001->estudiantes)->toHaveCount(1)
        ->and($docente1001->estudiantes->first()->carne)->toBe('C12345');

    // Poscondición: consejero_id de C12345 apunta al docente 1001
    expect($estudiante->fresh()->consejero_id)->toBe($docente1001->id);
});

// =========================================================================
// CP-09: Cambiar de consejero reemplaza al anterior, no lo suma
// =========================================================================
test('CP-09 Cambiar de consejero reemplaza al anterior, no lo suma', function () {
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

    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $docente1001->id,
    ]);

    // Pasos: 1. Asignar el docente 1002 como consejero de C12345 y guardar
    $estudiante->consejero()->associate($docente1002);
    $estudiante->save();

    // 2. Recargar ambos docentes
    $docente1001->refresh();
    $docente1002->refresh();

    // Resultado esperado: El consejero de C12345 es 1002.
    // El docente 1001 tiene 0 estudiantes aconsejados; el 1002 tiene 1
    expect($estudiante->consejero->codEmpleado)->toBe(1002)
        ->and($docente1001->estudiantes)->toHaveCount(0)
        ->and($docente1002->estudiantes)->toHaveCount(1)
        ->and($docente1002->estudiantes->first()->carne)->toBe('C12345');

    // Poscondición: C12345 tiene un único consejero_id (1002)
    expect($estudiante->fresh()->consejero_id)->toBe($docente1002->id);
});

// =========================================================================
// CP-10: Al matricular, la nota queda vacía
// =========================================================================
test('CP-10 Al matricular, la nota queda vacía', function () {
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

    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $docente1001->id,
    ]);

    // Pasos: 1. Matricular C12345 en el grupo 01. 2. Leer $grupo->estudiantes->first()->pivot
    $grupo->estudiantes()->attach($estudiante->id);
    $pivote = $grupo->estudiantes->first()->pivot;

    // Resultado esperado: El pivote es una instancia de EstudianteMatriculado y su nota es null
    expect($pivote)->toBeInstanceOf(EstudianteMatriculado::class)
        ->and($pivote->nota)->toBeNull();

    // Poscondición: 1 registro en estudiantes_matriculados con grupo_id y estudiante_id correctos
    expect(EstudianteMatriculado::count())->toBe(1);
    $registro = EstudianteMatriculado::first();
    expect($registro->grupo_id)->toBe($grupo->id)
        ->and($registro->estudiante_id)->toBe($estudiante->id);
});

// =========================================================================
// CP-11: Registrar la nota al finalizar el curso
// =========================================================================
test('CP-11 Registrar la nota al finalizar el curso', function () {
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

    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $docente1001->id,
    ]);

    $grupo->estudiantes()->attach($estudiante->id, ['nota' => null]);

    // Pasos: 1. registrarNota(C12345, 8.5) 2. Consultar la matrícula con EstudianteMatriculado::where(...)->first()
    $grupo->registrarNota($estudiante, 8.5);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    // Resultado esperado: La nota guardada es 8.5 y llega como float
    expect($matricula->nota)->toBe(8.5)
        ->and($matricula->nota)->toBeFloat();

    // Poscondición: Solo cambió la nota de C12345; el resto de la matrícula queda igual
    expect(EstudianteMatriculado::count())->toBe(1)
        ->and($matricula->grupo_id)->toBe($grupo->id)
        ->and($matricula->estudiante_id)->toBe($estudiante->id);
});

// =========================================================================
// CP-12: Rechazar notas menores que 0 o mayores que 10
// =========================================================================
test('CP-12 Rechazar notas menores que 0 o mayores que 10', function (float $valor) {
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

    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $docente1001->id,
    ]);

    $grupo->estudiantes()->attach($estudiante->id, ['nota' => null]);

    // Resultado esperado: Cada llamada lanza InvalidArgumentException
    expect(fn () => $grupo->registrarNota($estudiante, $valor))
        ->toThrow(InvalidArgumentException::class);

    // Poscondición: La nota de C12345 sigue en null
    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBeNull();
})->with([-1, 10.5]);

// =========================================================================
// CP-13: Un estudiante no se matricula dos veces en el mismo grupo
// =========================================================================
test('CP-13 Un estudiante no se matricula dos veces en el mismo grupo', function () {
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

    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $docente1001->id,
    ]);

    $grupo->estudiantes()->attach($estudiante->id);

    // Resultado esperado: Se lanza Illuminate\Database\QueryException (índice único)
    expect(fn () => $grupo->estudiantes()->attach($estudiante->id))
        ->toThrow(QueryException::class);

    // Poscondición: Hay un solo registro de C12345 en el grupo 01
    $conteo = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->count();

    expect($conteo)->toBe(1);
});

// =========================================================================
// C3: Pruebas adicionales para eliminar mutantes sobrevivientes
// =========================================================================
test('C3-01 Aceptar notas exactamente en los límites válidos 0 y 10', function (float $notaLimite) {
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

    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $docente1001->id,
    ]);

    $grupo->estudiantes()->attach($estudiante->id, ['nota' => null]);

    $grupo->registrarNota($estudiante, $notaLimite);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBe($notaLimite);
})->with([0.0, 10.0]);

test('C3-02 Convertir atributos numéricos de Grupo a integer mediante casts', function () {
    $grupo = new Grupo([
        'numeroCiclo' => '2',
        'ano' => '2026',
        'numeroGrupo' => '1',
        'abierto' => 1,
    ]);

    expect($grupo->numeroCiclo)->toBe(2)
        ->and($grupo->ano)->toBe(2026)
        ->and($grupo->numeroGrupo)->toBe(1)
        ->and($grupo->abierto)->toBeTrue();
});