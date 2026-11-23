<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'fecha_inicio', 'fecha_fin', 'estado'])]
class Periodo extends Model
{
    use HasFactory;

    protected $table = 'periodos';

    protected $hidden = ['activo_unico'];

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public static function nombreParaFechas(CarbonInterface $inicio, CarbonInterface $fin): string
    {
        $mesInicio = ucfirst($inicio->copy()->locale('es')->monthName);
        $mesFin = ucfirst($fin->copy()->locale('es')->monthName);

        return $inicio->year === $fin->year
            ? "$mesInicio - $mesFin {$fin->year}"
            : "$mesInicio {$inicio->year} - $mesFin {$fin->year}";
    }

    /**
     * @return HasMany<GrupoAcademico, $this>
     */
    public function gruposAcademicos(): HasMany
    {
        return $this->hasMany(GrupoAcademico::class, 'periodo_id');
    }
}
