<?php

namespace App\Servicios;

use App\Models\Proyecto;
use Illuminate\Support\Carbon;

class EstadoGuias
{
    public const ESTADOS = [
        'finalizada' => 'Finalizada con PDF',
        'lista_pdf' => 'Lista para generar PDF',
        'necesita_prorroga' => 'Requiere decisión de cierre',
        'prorroga_activa' => 'Prórroga activa',
        'prorroga_vencida' => 'Prórroga vencida',
        'cerrada_pendientes' => 'Cerrada con pendientes',
        'en_curso' => 'En curso con pendientes',
        'borrador' => 'Guía en borrador',
    ];

    public function proyecto(Proyecto $proyecto): array
    {
        $datos = app(DocumentosGuias::class)->datos($proyecto, false);
        $guia = $datos['guia'];
        $cierre = app(PlazosProyecto::class)->cierre($proyecto);
        $documento = $proyecto->documentosFinales->sortByDesc('id')->first();
        $apartados = [];
        foreach ($guia->apartados->sortBy('orden') as $apartado) {
            $fila = $datos['filas'][$apartado->id];
            $requeridas = collect($fila['firmantes'])->where('requerida', true);
            $firmadas = $requeridas->filter(fn ($f) => (bool) $f['revision'])->count();
            $correcciones = $fila['entrega']?->revisiones->whereIn('revisor_id', $requeridas->pluck('docente.id')->filter())
                ->whereIn('resultado', ['correccion', 'rechazada'])->isNotEmpty() ?? false;
            $completo = $fila['entrega'] && count($fila['firmantes']) > 0 && $firmadas === $requeridas->count() && ! $correcciones;
            $motivos = [];
            if (! $fila['entrega']) {
                $motivos[] = 'Falta entrega del equipo.';
            }
            if ($correcciones) {
                $motivos[] = 'La última entrega tiene correcciones o rechazo.';
            }
            if ($fila['entrega']) {
                foreach ($requeridas->filter(fn ($f) => ! $f['revision']) as $firmante) {
                    $motivos[] = 'Falta aprobación firmada de '.($firmante['docente']?->nombre ?? $firmante['etiqueta']).'.';
                }
            }
            if (! $fila['firmantes']) {
                $motivos[] = 'Falta configurar el evaluador.';
            }
            $apartados[] = [
                'titulo' => $apartado->titulo, 'orden' => $apartado->orden, 'fecha' => $fila['fecha_limite'],
                'entregada' => (bool) $fila['entrega'], 'version' => $fila['entrega']?->version,
                'firmadas' => $firmadas, 'requeridas' => $requeridas->count(), 'completo' => (bool) $completo,
                'correcciones' => $correcciones, 'motivos' => $motivos,
                'en_prorroga' => app(PlazosProyecto::class)->permite($proyecto, $apartado),
            ];
        }
        $periodo = $guia->periodoFin ?? $guia->periodo;
        $termino = $guia->estado === 'cerrada' || $periodo->estado === 'cerrado' || Carbon::parse($periodo->fecha_fin)->endOfDay()->isPast();
        $prorrogas = $cierre?->prorrogas->where('ronda', $cierre->ronda) ?? collect();
        $activa = $cierre?->estado === 'prorroga' && $prorrogas->contains(fn ($p) => $p->fecha_limite->isFuture());
        $vencida = in_array($cierre?->estado, ['pendiente', 'prorroga'], true) && $prorrogas->isNotEmpty() && ! $prorrogas->contains(fn ($p) => $p->fecha_limite->isFuture());
        $archivada = $documento && ($cierre?->estado === 'completo' || $proyecto->estado === 'finalizado');
        $estado = match (true) {
            $cierre?->estado === 'cerrado' && count($cierre->pendientes) > 0 => 'cerrada_pendientes',
            (bool) $archivada, ! $datos['pendientes'] && (bool) $documento => 'finalizada',
            $guia->estado === 'borrador' => 'borrador',
            ! $datos['pendientes'] => 'lista_pdf',
            $activa => 'prorroga_activa',
            $vencida => 'prorroga_vencida',
            $termino => 'necesita_prorroga',
            default => 'en_curso',
        };

        return ['proyecto' => $proyecto, 'guia' => $guia, 'cierre' => $cierre, 'documento' => $documento,
            'estado' => $estado, 'etiqueta' => self::ESTADOS[$estado], 'apartados' => $apartados,
            'total' => count($apartados), 'completos' => collect($apartados)->where('completo', true)->count(),
            'entregados' => collect($apartados)->where('entregada', true)->count(),
            'firmadas' => collect($apartados)->sum('firmadas'), 'requeridas' => collect($apartados)->sum('requeridas'),
            'fecha_prorroga' => $prorrogas->max('fecha_limite'),
            'cambios_despues_cierre' => (bool) $archivada && count($datos['pendientes']) > 0,
            'requiere_decision' => in_array($estado, ['necesita_prorroga', 'prorroga_vencida'], true),
            'pendientes' => $cierre?->estado === 'cerrado' ? $cierre->pendientes : $datos['pendientes'],
        ];
    }
}
