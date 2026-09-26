<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvisoAsignacion extends Model
{
    protected $table = 'avisos_asignaciones';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['programado_en' => 'datetime', 'despachado_en' => 'datetime', 'enviado_en' => 'datetime'];
    }
}
