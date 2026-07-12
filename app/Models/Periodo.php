<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'fecha_inicio', 'fecha_fin', 'estado'])]
class Periodo extends Model
{
    use HasFactory;

    protected $table = 'periodos';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    /**
     * @return HasMany<GrupoAcademico, $this>
     */
    public function gruposAcademicos(): HasMany
    {
        return $this->hasMany(GrupoAcademico::class, 'periodo_id');
    }
}
