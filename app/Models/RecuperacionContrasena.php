<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecuperacionContrasena extends Model
{
    protected $table = 'recuperaciones_contrasenas';

    protected $guarded = ['id'];

    protected $hidden = ['correo_hash', 'codigo_hash', 'navegador_hash', 'autorizacion_hash'];

    protected function casts(): array
    {
        return ['reenviar_en' => 'datetime', 'expira_en' => 'datetime', 'verificado_en' => 'datetime'];
    }
}
