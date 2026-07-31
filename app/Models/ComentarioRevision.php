<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['revision_id', 'autor_id', 'comentario', 'visible_estudiante'])]
class ComentarioRevision extends Model
{
    protected $table = 'comentarios_revision';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(Revision::class, 'revision_id');
    }

    protected function casts(): array
    {
        return ['visible_estudiante' => 'boolean'];
    }
}
