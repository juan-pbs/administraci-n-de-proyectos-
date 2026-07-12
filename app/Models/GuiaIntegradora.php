<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['periodo_id', 'periodo_fin_id', 'asignatura_id', 'creado_por', 'nombre', 'cuatrimestre', 'competencias_evaluar', 'objetivo_aprendizaje', 'version', 'estado'])]
class GuiaIntegradora extends Model
{
    use HasFactory;

    protected $table = 'guias_integradoras';

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
     * @return BelongsTo<Periodo, $this>
     */
    public function periodoFin(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'periodo_fin_id');
    }

    /**
     * @return BelongsTo<Asignatura, $this>
     */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class, 'asignatura_id');
    }

    /**
     * @return HasMany<ApartadoGuia, $this>
     */
    public function apartados(): HasMany
    {
        return $this->hasMany(ApartadoGuia::class, 'guia_integradora_id');
    }
}
