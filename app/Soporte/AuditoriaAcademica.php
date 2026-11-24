<?php

namespace App\Soporte;

use Illuminate\Support\Facades\DB;

class AuditoriaAcademica
{
    public static function registrar(?int $actor, string $modulo, int $id, string $accion, array $antes, array $despues, ?string $motivo = null): void
    {
        DB::table('auditoria_academica')->insert([
            'actor_id' => $actor, 'modulo' => $modulo, 'registro_id' => $id, 'accion' => $accion,
            'motivo' => $motivo, 'antes' => json_encode($antes, JSON_THROW_ON_ERROR),
            'despues' => json_encode($despues, JSON_THROW_ON_ERROR), 'creado_en' => now(),
        ]);
    }
}
