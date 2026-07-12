<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['apartado_guia_id', 'asignatura_id', 'docente_id', 'orden', 'etiqueta', 'requerida'])]
class FirmaApartadoGuia extends Model
{
    use HasFactory;

    protected $table = 'firmas_apartado_guia';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    /**
     * @return BelongsTo<ApartadoGuia, $this>
     */
    public function apartadoGuia(): BelongsTo
    {
        return $this->belongsTo(ApartadoGuia::class, 'apartado_guia_id');
    }

    /**
     * @return BelongsTo<Asignatura, $this>
     */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class, 'asignatura_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requerida' => 'boolean',
        ];
    }
}
