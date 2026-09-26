<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoFinal extends Model
{
    protected $table = 'documentos_finales';

    protected $guarded = ['id'];

    protected $hidden = ['pdf'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Un PDF emitido no puede sobrescribirse. Genera una nueva emisión.'));
        static::deleting(fn () => throw new \LogicException('Los PDF emitidos se conservan en el historial.'));
    }

    protected function casts(): array
    {
        return ['pdf' => 'encrypted'];
    }
}
