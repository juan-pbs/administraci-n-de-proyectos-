<?php

namespace App\Soporte;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProtegeHistorial
{
    public static function sinReferencias(int $id, array $referencias, string $mensaje): void
    {
        foreach ($referencias as $tabla => $columna) {
            if (DB::table($tabla)->where($columna, $id)->exists()) {
                throw ValidationException::withMessages(['eliminar' => $mensaje]);
            }
        }
    }
}
