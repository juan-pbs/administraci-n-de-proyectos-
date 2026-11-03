<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CorreosPruebaSeeder extends Seeder
{
    public const CUENTAS = [
        'DOC-TI-01' => 'juanbarrerasanti@gmail.com',
        'DOC-TI-03' => 'jb8915736@gmail.com',
        'TI41011' => 'juanpbs2005@gmail.com',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Los correos de práctica solo se asignan en local o pruebas.');
        }
        if (! User::query()->whereIn('matricula', array_keys(self::CUENTAS))->exists()) {
            return;
        }

        DB::transaction(function (): void {
            foreach (self::CUENTAS as $matricula => $correo) {
                if (User::query()->where('matricula', $matricula)->update(['correo' => $correo]) !== 1) {
                    throw new RuntimeException("Falta la cuenta de práctica {$matricula}.");
                }
            }
        });
    }
}
