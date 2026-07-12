<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['grupo_academico_id', 'lider_id', 'nombre', 'estado'])]
class Equipo extends Model
{
    use HasFactory;

    protected $table = 'equipos';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    /**
     * @return BelongsTo<GrupoAcademico, $this>
     */
    public function grupoAcademico(): BelongsTo
    {
        return $this->belongsTo(GrupoAcademico::class, 'grupo_academico_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lider_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function integrantes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'integrantes_equipo', 'equipo_id', 'estudiante_id')
            ->wherePivot('activo', true)
            ->withPivot(['activo'])
            ->withTimestamps('creado_en', 'actualizado_en');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function asesores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'asesores_equipo', 'equipo_id', 'asesor_id')
            ->wherePivot('activo', true)
            ->withPivot(['principal', 'activo'])
            ->withTimestamps('creado_en', 'actualizado_en');
    }

    /**
     * @return HasMany<Proyecto, $this>
     */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'equipo_id');
    }
}
