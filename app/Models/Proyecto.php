<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['guia_integradora_id', 'equipo_id', 'titulo', 'descripcion', 'estado'])]
class Proyecto extends Model
{
    use HasFactory;

    protected $table = 'proyectos';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public function cierre(): HasOne
    {
        return $this->hasOne(CierreProyecto::class, 'proyecto_id');
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class, 'proyecto_id');
    }

    public function documentosFinales(): HasMany
    {
        return $this->hasMany(DocumentoFinal::class, 'proyecto_id');
    }

    /**
     * @return BelongsTo<GuiaIntegradora, $this>
     */
    public function guiaIntegradora(): BelongsTo
    {
        return $this->belongsTo(GuiaIntegradora::class, 'guia_integradora_id');
    }

    /**
     * @return BelongsTo<Equipo, $this>
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'docentes_proyecto', 'proyecto_id', 'docente_id')
            ->wherePivot('activo', true)
            ->withPivot(['tipo_participacion', 'activo'])
            ->withTimestamps('creado_en', 'actualizado_en');
    }

    /**
     * @return BelongsToMany<Asignatura, $this>
     */
    public function asignaturas(): BelongsToMany
    {
        return $this->belongsToMany(Asignatura::class, 'asignaturas_proyecto', 'proyecto_id', 'asignatura_id')
            ->withPivot(['docente_id', 'participa_evaluacion'])
            ->withTimestamps('creado_en', 'actualizado_en');
    }
}
