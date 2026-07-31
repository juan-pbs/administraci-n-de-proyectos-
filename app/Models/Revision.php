<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['entrega_id', 'asignacion_revision_id', 'revisor_id', 'resultado', 'calificacion', 'observaciones', 'revisado_en'])]
class Revision extends Model
{
    protected $table = 'revisiones';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    public function entrega(): BelongsTo { return $this->belongsTo(Entrega::class, 'entrega_id'); }
    public function comentarios(): HasMany { return $this->hasMany(ComentarioRevision::class, 'revision_id'); }

    protected function casts(): array
    {
        return ['calificacion' => 'decimal:2', 'revisado_en' => 'datetime'];
    }
}
