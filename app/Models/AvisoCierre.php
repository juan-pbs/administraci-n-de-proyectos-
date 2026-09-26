<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvisoCierre extends Model
{
    protected $table = 'avisos_cierres';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['despachado_en' => 'datetime', 'enviado_en' => 'datetime'];
    }
}
