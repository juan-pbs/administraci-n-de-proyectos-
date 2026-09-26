<?php

namespace App\Servicios;

use App\Jobs\EnviarAvisoAsignacion;
use App\Models\ApartadoGuia;
use App\Models\AvisoAsignacion;
use App\Models\Entrega;
use App\Models\Proyecto;
use App\Models\User;

class AvisosAsignaciones
{
    public function huella(Proyecto $proyecto, ApartadoGuia $apartado): string
    {
        return hash('sha256', json_encode([$proyecto->titulo, $proyecto->descripcion, $apartado->titulo, $apartado->descripcion,
            app(PlazosProyecto::class)->fecha($proyecto, $apartado)?->toIso8601String(), $apartado->ponderacion, $apartado->requiere_codigo, $apartado->requiere_documento], JSON_THROW_ON_ERROR));
    }

    public function vigente(Proyecto $proyecto, User $estudiante, ?ApartadoGuia $apartado = null): bool
    {
        $proyecto->loadMissing(['guiaIntegradora.periodo', 'equipo.grupoAcademico']);

        $plazos = app(PlazosProyecto::class);
        $abierto = $plazos->cierre($proyecto)
            ? ($apartado && $plazos->permite($proyecto, $apartado))
            : ($proyecto->guiaIntegradora->estado === 'publicada' && $proyecto->guiaIntegradora->periodo->estado === 'activo');

        return $proyecto->estado === 'en_proceso' && $abierto && $proyecto->equipo->estado === 'activo'
            && $estudiante->estado === 'activo' && $estudiante->hasRole('estudiante')
            && $proyecto->equipo->integrantes()->where('usuarios.id', $estudiante->id)->exists();
    }

    public function necesitaRecordatorio(Proyecto $proyecto, ApartadoGuia $apartado): bool
    {
        $entrega = Entrega::query()->with('revisiones')->where('proyecto_id', $proyecto->id)->where('apartado_guia_id', $apartado->id)->orderByDesc('version')->first();

        return ! $entrega || in_array($entrega->estado, ['correccion', 'rechazada'], true)
            || $entrega->revisiones->contains(fn ($r) => in_array($r->resultado, ['correccion', 'rechazada'], true));
    }

    public function sincronizar(): int
    {
        $cantidad = 0;
        Proyecto::query()->with(['guiaIntegradora.apartados', 'guiaIntegradora.periodo', 'equipo.integrantes.role', 'equipo.grupoAcademico'])
            ->where('estado', 'en_proceso')->where(fn ($q) => $q
            ->whereHas('guiaIntegradora', fn ($g) => $g->where('estado', 'publicada')->whereHas('periodo', fn ($p) => $p->where('estado', 'activo')))
            ->orWhereHas('cierre', fn ($c) => $c->where('estado', 'prorroga')->whereHas('prorrogas', fn ($p) => $p->whereColumn('prorrogas_apartados.ronda', 'cierres_proyectos.ronda')->where('fecha_limite', '>', now()))))
            ->chunkById(100, function ($proyectos) use (&$cantidad) {
                foreach ($proyectos as $proyecto) {
                    foreach ($proyecto->guiaIntegradora->apartados as $apartado) {
                        $fecha = app(PlazosProyecto::class)->fecha($proyecto, $apartado);
                        if ($fecha?->isPast()) {
                            continue;
                        }
                        $huella = $this->huella($proyecto, $apartado);
                        foreach ($proyecto->equipo->integrantes as $alumno) {
                            if (! $this->vigente($proyecto, $alumno, $apartado)) {
                                continue;
                            }
                            $anterior = AvisoAsignacion::query()->where('proyecto_id', $proyecto->id)->where('apartado_guia_id', $apartado->id)
                                ->where('estudiante_id', $alumno->id)->whereIn('tipo', ['asignacion', 'cambio'])->latest('id')->first();
                            if (! $anterior || $anterior->huella !== $huella) {
                                // Incluye el aviso anterior para distinguir cambios A -> B -> A.
                                $this->crear($proyecto, $apartado, $alumno, $huella, $anterior ? 'cambio' : 'asignacion', now(), (string) ($anterior?->id ?? 0));
                            }
                            if ($fecha && $this->necesitaRecordatorio($proyecto, $apartado)) {
                                $horas = config('asignaciones.recordatorios_horas');
                                foreach ($horas as $i => $hora) {
                                    $programado = $fecha->copy()->subHours($hora);
                                    $finVentana = isset($horas[$i + 1]) ? $fecha->copy()->subHours($horas[$i + 1]) : $fecha;
                                    if (now()->gte($programado) && now()->lt($finVentana)) {
                                        $this->crear($proyecto, $apartado, $alumno, $huella, 'recordatorio_'.$hora, $programado);
                                    }
                                }
                            }
                        }
                    }
                }
            });
        AvisoAsignacion::query()->where('estado', 'pendiente')->where('programado_en', '<=', now())
            ->where(fn ($q) => $q->whereNull('despachado_en')->orWhere('despachado_en', '<', now()->subMinutes(15)))
            ->chunkById(100, function ($avisos) use (&$cantidad) {
                foreach ($avisos as $aviso) {
                    $claimed = AvisoAsignacion::query()->whereKey($aviso->id)->where('estado', 'pendiente')
                        ->where(fn ($q) => $q->whereNull('despachado_en')->orWhere('despachado_en', '<', now()->subMinutes(15)))
                        ->update(['despachado_en' => now()]);
                    if ($claimed) {
                        EnviarAvisoAsignacion::dispatch($aviso->id);
                        $cantidad++;
                    }
                }
            });

        return $cantidad;
    }

    private function crear($proyecto, $apartado, $alumno, string $huella, string $tipo, $fecha, string $cambio = ''): void
    {
        AvisoAsignacion::query()->firstOrCreate(['clave' => hash('sha256', implode('|', [$proyecto->id, $apartado->id, $alumno->id, $huella, $tipo, $cambio]))], [
            'proyecto_id' => $proyecto->id, 'apartado_guia_id' => $apartado->id, 'estudiante_id' => $alumno->id,
            'huella' => $huella, 'tipo' => $tipo, 'programado_en' => $fecha,
        ]);
    }
}
