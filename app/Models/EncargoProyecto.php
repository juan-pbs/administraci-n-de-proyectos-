<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['encargado_id', 'periodo_id', 'carrera_id', 'cuatrimestre', 'activo'])]
class EncargoProyecto extends Model
{
    protected $table = 'encargos_proyecto';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    public function encargado(): BelongsTo { return $this->belongsTo(User::class, 'encargado_id'); }
    public function periodo(): BelongsTo { return $this->belongsTo(Periodo::class, 'periodo_id'); }
    public function carrera(): BelongsTo { return $this->belongsTo(Carrera::class, 'carrera_id'); }
}
