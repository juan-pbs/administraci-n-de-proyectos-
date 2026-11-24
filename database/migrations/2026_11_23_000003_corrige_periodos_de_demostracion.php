<?php

use App\Soporte\AuditoriaAcademica;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $correcciones = [
            ['Enero - Abril 2025', '2025-03-01', '2025-06-30', 'Marzo - Junio 2025'],
            ['Mayo - Agosto 2025', '2025-07-01', '2025-10-31', 'Julio - Octubre 2025'],
            ['Mayo - Agosto 2026', '2026-07-01', '2026-10-31', 'Julio - Octubre 2026'],
            ['Septiembre - Diciembre 2026', '2026-11-01', '2027-02-28', 'Noviembre 2026 - Febrero 2027'],
            ['Enero - Abril 2027', '2027-03-01', '2027-06-30', 'Marzo - Junio 2027'],
        ];

        DB::transaction(function () use ($correcciones): void {
            foreach ($correcciones as [$anterior, $inicio, $fin, $nuevo]) {
                $periodo = DB::table('periodos')->where('nombre', $anterior)
                    ->whereDate('fecha_inicio', $inicio)->whereDate('fecha_fin', $fin)->lockForUpdate()->first();
                if (! $periodo) {
                    continue;
                }
                DB::table('periodos')->where('id', $periodo->id)->update(['nombre' => $nuevo, 'actualizado_en' => now()]);
                $guias = DB::table('guias_integradoras')->where('periodo_id', $periodo->id)
                    ->where('nombre', 'like', 'Guía integradora % - '.$anterior.'%')->get();
                foreach ($guias as $guia) {
                    $nombre = str_replace($anterior, $nuevo, $guia->nombre);
                    DB::table('guias_integradoras')->where('id', $guia->id)->update(['nombre' => $nombre, 'actualizado_en' => now()]);
                    if ($guia->estado === 'borrador' && $periodo->estado === 'borrador' && $guia->periodo_fin_id === $periodo->id) {
                        $this->corregirPlazos($guia->id, $inicio, $fin);
                    }
                }
                AuditoriaAcademica::registrar(null, 'periodos', $periodo->id, 'corregir_nombre',
                    ['nombre' => $anterior, 'inicio' => $inicio, 'fin' => $fin, 'estado' => $periodo->estado],
                    ['nombre' => $nuevo, 'inicio' => $inicio, 'fin' => $fin, 'estado' => $periodo->estado, 'guias' => $guias->pluck('id')->all()],
                    'Corrección del nombre de un periodo del conjunto de demostración.');
            }
        });
    }

    private function corregirPlazos(int $guiaId, string $inicio, string $fin): void
    {
        $apartados = DB::table('apartados_guia as a')->where('a.guia_integradora_id', $guiaId)
            ->whereIn('a.orden', [1, 2, 3])->whereDate('a.fecha_limite', '<', $inicio)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('entregas')->whereColumn('entregas.apartado_guia_id', 'a.id'))
            ->get();
        foreach ($apartados as $apartado) {
            $fecha = Carbon::parse($inicio)->addWeeks($apartado->orden)->endOfDay();
            if ($fecha->toDateString() <= $fin) {
                DB::table('apartados_guia')->where('id', $apartado->id)->update(['fecha_limite' => $fecha, 'actualizado_en' => now()]);
                AuditoriaAcademica::registrar(null, 'apartados', $apartado->id, 'corregir_plazo',
                    ['fecha_limite' => $apartado->fecha_limite], ['fecha_limite' => $fecha->toDateTimeString()],
                    'Ajuste de un plazo de demostración al rango de su periodo.');
            }
        }
    }

    public function down(): void
    {
        // Los nombres y plazos corregidos se conservan al revertir el despliegue.
    }
};
