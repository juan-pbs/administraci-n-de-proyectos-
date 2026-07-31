<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['entrega_id', 'nombre_original', 'ruta', 'tipo_archivo', 'tamano'])]
class ArchivoEntrega extends Model
{
    protected $table = 'archivos_entrega';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(Entrega::class, 'entrega_id');
    }

    public function esComprimido(): bool
    {
        return in_array(strtolower(pathinfo($this->nombre_original, PATHINFO_EXTENSION)), ['zip', 'rar', '7z', 'tar', 'gz', 'tgz'], true);
    }

    public function extension(): string
    {
        return strtoupper(pathinfo($this->nombre_original, PATHINFO_EXTENSION) ?: 'ARCHIVO');
    }
}
