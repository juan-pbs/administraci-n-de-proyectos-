<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'clave', 'estado'])]
class Carrera extends Model
{
    use HasFactory;

    protected $table = 'carreras';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    /**
     * @return HasMany<GrupoAcademico, $this>
     */
    public function gruposAcademicos(): HasMany
    {
        return $this->hasMany(GrupoAcademico::class, 'carrera_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'carrera_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'docentes_carrera', 'carrera_id', 'docente_id')
            ->withPivot(['activo'])
            ->withTimestamps('creado_en', 'actualizado_en');
    }
}
