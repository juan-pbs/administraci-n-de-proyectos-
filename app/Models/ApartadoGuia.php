<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['guia_integradora_id', 'orden', 'titulo', 'descripcion', 'fecha_limite', 'ponderacion', 'requiere_documento', 'requiere_codigo'])]
class ApartadoGuia extends Model
{
    use HasFactory;

    protected $table = 'apartados_guia';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    /**
     * @return BelongsTo<GuiaIntegradora, $this>
     */
    public function guiaIntegradora(): BelongsTo
    {
        return $this->belongsTo(GuiaIntegradora::class, 'guia_integradora_id');
    }

    /**
     * @return BelongsToMany<Asignatura, $this>
     */
    public function asignaturasContribuyentes(): BelongsToMany
    {
        return $this->belongsToMany(Asignatura::class, 'asignaturas_apartado_guia', 'apartado_guia_id', 'asignatura_id')
            ->withPivot(['rol_contribucion', 'requiere_firma'])
            ->withTimestamps('creado_en', 'actualizado_en');
    }

    /**
     * @return HasMany<FirmaApartadoGuia, $this>
     */
    public function firmas(): HasMany
    {
        return $this->hasMany(FirmaApartadoGuia::class, 'apartado_guia_id')->orderBy('orden');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_limite' => 'datetime',
            'requiere_documento' => 'boolean',
            'requiere_codigo' => 'boolean',
        ];
    }
}
