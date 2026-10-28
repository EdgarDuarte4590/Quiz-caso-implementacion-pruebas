<?php

namespace Tests\Unit;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoTest extends TestCase
{
    use RefreshDatabase;

    public function test_instancia_grupo_contiene_toda_la_informacion_real_asociada(): void
    {
        // ==========================================
        // 1. ARRANGE (Preparar)
        // ==========================================
        $curso = Curso::factory()->create([
            'sigla' => 'IF6100',
            'nombreCurso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);

        // Regla de negocio: Cada grupo puede ser impartido por 1 o 2 docentes
        $docenteTitular = Docente::factory()->create([
            'codEmpleado' => 10452,
            'nombreEmpleado' => 'Carlos',
            'apellidosEmpleado' => 'Mora Vargas',
        ]);

        $docenteAsociado = Docente::factory()->create([
            'codEmpleado' => 10890,
            'nombreEmpleado' => 'Laura',
            'apellidosEmpleado' => 'Castro Jiménez',
        ]);

        // Regla de negocio y Diagrama: Mínimo 15 estudiantes (15..*) y cada uno con 1 profesor consejero
        $estudiantes = Estudiante::factory()->count(15)->create([
            'docente_id' => $docenteTitular->id,
        ]);

        // ==========================================
        // 2. ACT (Actuar)
        // ==========================================
        $grupo = Grupo::factory()->create([
            'numeroCiclo' => 2,
            'ano' => 2026,
            'numeroGrupo' => 1,
            'abierto' => true,
            'curso_id' => $curso->id,
        ]);

        // Asociar los 2 docentes que imparten el grupo
        $grupo->docentes()->attach([$docenteTitular->id, $docenteAsociado->id]);

        // Matricular a los 15 estudiantes en el grupo registrando su nota en EstudianteMatriculado
        foreach ($estudiantes as $estudiante) {
            $grupo->estudiantes()->attach($estudiante->id, [
                'nota' => 92.5,
            ]);
        }

        // Recargar el modelo Grupo con todas sus relaciones reales asociadas
        $grupoConRelaciones = Grupo::with([
            'curso',
            'docentes',
            'estudiantes.consejero',
            'estudiantesMatriculados',
        ])->find($grupo->id);

        // ==========================================
        // 3. ASSERT (Afirmar)
        // ==========================================
        // Verificar atributos propios de la instancia Grupo
        $this->assertInstanceOf(Grupo::class, $grupoConRelaciones);
        $this->assertEquals(2, $grupoConRelaciones->numeroCiclo);
        $this->assertEquals(2026, $grupoConRelaciones->ano);
        $this->assertEquals(1, $grupoConRelaciones->numeroGrupo);
        $this->assertTrue($grupoConRelaciones->abierto);

        // Verificar información real del Curso asociado (multiplicidad 1)
        $this->assertNotNull($grupoConRelaciones->curso);
        $this->assertEquals('IF6100', $grupoConRelaciones->curso->sigla);
        $this->assertEquals('Análisis y Diseño de Sistemas', $grupoConRelaciones->curso->nombreCurso);
        $this->assertEquals(4, $grupoConRelaciones->curso->creditos);

        // Verificar información real de los Docentes que imparten el grupo (multiplicidad 1..2)
        $this->assertCount(2, $grupoConRelaciones->docentes);
        $this->assertGreaterThanOrEqual(1, $grupoConRelaciones->docentes->count());
        $this->assertLessThanOrEqual(2, $grupoConRelaciones->docentes->count());
        $this->assertTrue($grupoConRelaciones->docentes->contains('codEmpleado', 10452));

        // Verificar Estudiantes matriculados (multiplicidad 15..*), su consejero y su nota en EstudianteMatriculado
        $this->assertCount(15, $grupoConRelaciones->estudiantes);
        $this->assertGreaterThanOrEqual(15, $grupoConRelaciones->estudiantes->count());
        $this->assertCount(15, $grupoConRelaciones->estudiantesMatriculados);

        $primerEstudiante = $grupoConRelaciones->estudiantes->first();
        $this->assertNotNull($primerEstudiante->consejero);
        $this->assertEquals(10452, $primerEstudiante->consejero->codEmpleado);
        $this->assertInstanceOf(EstudianteMatriculado::class, $primerEstudiante->pivot);
        $this->assertEquals(92.5, $primerEstudiante->pivot->nota);
    }
}