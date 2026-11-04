<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursoTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_guardar_un_curso_mediante_el_controlador(): void
    {
        // ==========================================
        // 1. ARRANGE (Preparar)
        // ==========================================
        $datosCurso = [
            'sigla' => 'IF6100',
            'nombreCurso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ];

        // ==========================================
        // 2. ACT (Actuar)
        // ==========================================
        $response = $this->post('/cursos', $datosCurso);

        // ==========================================
        // 3. ASSERT (Afirmar)
        // ==========================================
        $response->assertStatus(201);

        $this->assertDatabaseHas('cursos', [
            'sigla' => 'IF6100',
            'nombreCurso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);
    }
}