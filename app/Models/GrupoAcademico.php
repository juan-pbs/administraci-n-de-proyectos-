<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['periodo_id', 'carrera_id', 'lider_proyecto_id', 'asignatura_lider_id', 'nombre', 'grado', 'grupo'])]
class GrupoAcademico extends Model
{
    use HasFactory;

    protected $table = 'grupos_academicos';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    /**
     * @return BelongsTo<Periodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    /**
     * @return BelongsTo<Carrera, $this>
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function liderProyecto(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lider_proyecto_id');
    }

    public function asignaturaLider(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class, 'asignatura_lider_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function alumnos(): HasMany
    {
        return $this->hasMany(User::class, 'grupo_academico_id');
    }

    /**
     * @return HasMany<Equipo, $this>
     */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'grupo_academico_id');
    }
}
