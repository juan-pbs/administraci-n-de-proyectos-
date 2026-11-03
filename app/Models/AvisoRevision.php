<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvisoRevision extends Model
{
    protected $table = 'avisos_revisiones';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['programado_en' => 'datetime', 'despachado_en' => 'datetime', 'enviado_en' => 'datetime'];
    }
}
