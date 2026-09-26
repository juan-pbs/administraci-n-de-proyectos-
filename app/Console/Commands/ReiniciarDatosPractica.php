<?php

namespace App\Console\Commands;

use Database\Seeders\EscenariosAcademicosSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema, Storage};
use RuntimeException;
use ZipArchive;

class ReiniciarDatosPractica extends Command
{
    protected $signature = 'datos:reiniciar {--confirmar= : Nombre exacto de la base local que se reemplazará}';
    protected $description = 'Respalda la base local y sus archivos, y reemplaza los registros por escenarios académicos';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing']) || $this->option('confirmar') !== DB::connection()->getDatabaseName()) {
            $this->error('Solo se permite en local/pruebas, indicando --confirmar con el nombre exacto de la base.');
            return self::FAILURE;
        }
        $esquema = DB::connection()->getDriverName() === 'sqlite' ? 'main' : DB::connection()->getDatabaseName();
        $tablas = collect(Schema::getTables($esquema))->pluck('name')->reject(fn ($nombre) => $nombre === 'migrations')->values();
        $disk = Storage::disk('local');
        $respaldo = 'respaldos/antes-reinicio-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.zip';
        $disk->makeDirectory('respaldos');
        $zip = new ZipArchive;
        if ($zip->open($disk->path($respaldo), ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('No se pudo crear el respaldo; la base permanece intacta.');
        }
        $registros = [];
        foreach ($tablas as $tabla) { $registros[$tabla] = DB::table($tabla)->get()->all(); }
        if (! $zip->addFromString('registros.json', json_encode($registros, JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('No se pudo respaldar la base.');
        }
        $zip->addFromString('clave-cifrado.txt', config('app.key'));
        foreach ($disk->allFiles() as $archivo) {
            if (str_starts_with($archivo, 'respaldos/') || str_starts_with($archivo, 'pdf-tmp/')) { continue; }
            if (! $zip->addFile($disk->path($archivo), 'archivos/'.$archivo)) { throw new RuntimeException('No se pudo respaldar un archivo.'); }
        }
        if (! $zip->close()) { throw new RuntimeException('No se pudo finalizar el respaldo.'); }
        @chmod($disk->path($respaldo), 0600);
        Schema::withoutForeignKeyConstraints(function () use ($tablas) {
            DB::transaction(function () use ($tablas) {
                foreach ($tablas as $tabla) { DB::table($tabla)->delete(); }
                app(EscenariosAcademicosSeeder::class)->run();
            });
        });
        $this->info('Registros reemplazados. Respaldo privado: '.$disk->path($respaldo));
        return self::SUCCESS;
    }
}
