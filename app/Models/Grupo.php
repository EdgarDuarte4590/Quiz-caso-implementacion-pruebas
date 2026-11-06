<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory;

    protected $fillable = [
        'numeroCiclo',
        'ano',
        'numeroGrupo',
        'abierto',
        'curso_id',
    ];

    protected function casts(): array
    {
        return [
            'numeroCiclo' => 'integer',
            'ano' => 'integer',
            'numeroGrupo' => 'integer',
            'abierto' => 'boolean',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'docente_grupo')->withTimestamps();
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_matriculados')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota')
            ->withTimestamps();
    }

    public function estudiantesMatriculados(): HasMany
    {
        return $this->hasMany(EstudianteMatriculado::class);
    }

    public function asignarCurso($cursoAsignado): void
    {
        $this->curso()->associate($cursoAsignado);
    }

    public function asignarDocente(Docente|int $docente): void
    {
        if (is_int($docente)) {
            $docente = Docente::where('codEmpleado', $docente)
                ->orWhere('id', $docente)
                ->firstOrFail();
        }

        if ($this->docentes()->count() >= 2) {
            throw new DomainException('Un grupo no puede tener más de 2 docentes.');
        }

        $this->docentes()->attach($docente->id);
    }

    public function puedeAbrirse(): bool
    {
        $cantidadDocentes = $this->docentes()->count();
        $cantidadEstudiantes = $this->estudiantes()->count();

        return $cantidadDocentes >= 1 && $cantidadDocentes <= 2 && $cantidadEstudiantes >= 15;
    }

    public function abrir(): void
    {
        if (! $this->puedeAbrirse()) {
            throw new DomainException('El grupo no cumple con los requisitos para abrirse.');
        }

        $this->update(['abierto' => true]);
    }
}