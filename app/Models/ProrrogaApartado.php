<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProrrogaApartado extends Model
{
    protected $table = 'prorrogas_apartados';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha_limite' => 'datetime'];
    }
}
