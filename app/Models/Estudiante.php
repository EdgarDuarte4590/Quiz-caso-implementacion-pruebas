<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    use HasFactory;

    protected $fillable = [
        'carne',
        'nombre',
        'apellidos',
        'docente_id',
    ];

    public function consejero(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'estudiante_matriculados')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota')
            ->withTimestamps();
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(EstudianteMatriculado::class);
    }
}