<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['proyecto_id', 'apartado_guia_id', 'equipo_id', 'entregado_por_id', 'version', 'estado', 'entregado_en'])]
class Entrega extends Model
{
    protected $table = 'entregas';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    public function proyecto(): BelongsTo { return $this->belongsTo(Proyecto::class, 'proyecto_id'); }
    public function apartado(): BelongsTo { return $this->belongsTo(ApartadoGuia::class, 'apartado_guia_id'); }
    public function equipo(): BelongsTo { return $this->belongsTo(Equipo::class, 'equipo_id'); }
    public function entregadoPor(): BelongsTo { return $this->belongsTo(User::class, 'entregado_por_id'); }
    public function revisiones(): HasMany { return $this->hasMany(Revision::class, 'entrega_id'); }
    public function archivos(): HasMany { return $this->hasMany(ArchivoEntrega::class, 'entrega_id'); }
    public function productosCodigo(): HasMany { return $this->hasMany(ProductoCodigo::class, 'entrega_id'); }

    protected function casts(): array
    {
        return ['entregado_en' => 'datetime'];
    }
}
