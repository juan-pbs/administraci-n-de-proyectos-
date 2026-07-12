<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['carrera_id', 'nombre', 'clave', 'grado', 'estado'])]
class Asignatura extends Model
{
    use HasFactory;

    protected $table = 'asignaturas';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    /**
     * @return BelongsTo<Carrera, $this>
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    /**
     * @return HasMany<GuiaIntegradora, $this>
     */
    public function guiasIntegradoras(): HasMany
    {
        return $this->hasMany(GuiaIntegradora::class, 'asignatura_id');
    }
}
