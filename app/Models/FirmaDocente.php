<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmaDocente extends Model
{
    protected $table = 'firmas_docentes';

    protected $guarded = ['id'];

    protected $hidden = ['imagen'];

    protected function casts(): array
    {
        return ['imagen' => 'encrypted'];
    }
}
