<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sigla' => 'required|string|max:20|unique:cursos,sigla',
            'nombreCurso' => 'required|string|max:255',
            'creditos' => 'required|integer|min:0',
        ]);

        $curso = Curso::create($validated);

        return response()->json($curso, 201);
    }
}