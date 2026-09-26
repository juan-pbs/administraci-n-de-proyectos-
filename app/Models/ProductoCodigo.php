<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['proyecto_id', 'entrega_id', 'repositorio_url', 'demostracion_url', 'archivo_fuente', 'version'])]
class ProductoCodigo extends Model
{
    protected $table = 'productos_codigo';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(Entrega::class, 'entrega_id');
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }
}
