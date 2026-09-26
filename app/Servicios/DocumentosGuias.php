<?php

namespace App\Servicios;

use App\Models\Entrega;
use App\Models\GuiaIntegradora;
use App\Models\Proyecto;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Arr;

class DocumentosGuias
{
    public function cargarGuia(GuiaIntegradora $guia): GuiaIntegradora
    {
        return $guia->loadMissing(['periodo', 'asignatura.carrera', 'apartados.asignaturasContribuyentes', 'apartados.firmas.docente', 'apartados.firmas.asignatura']);
    }

    public function datos(Proyecto $proyecto, bool $incluirImagenes = true): array
    {
        $proyecto->loadMissing(['guiaIntegradora', 'equipo.integrantes', 'equipo.grupoAcademico.carrera', 'equipo.grupoAcademico.liderProyecto', 'asignaturas']);
        $guia = $this->cargarGuia($proyecto->guiaIntegradora);
        $entregas = ($proyecto->relationLoaded('entregas') ? $proyecto->entregas->sortByDesc('version')
            : Entrega::query()->with('revisiones')->where('proyecto_id', $proyecto->id)->orderByDesc('version')->get())
            ->unique('apartado_guia_id')->keyBy('apartado_guia_id');
        $filas = [];
        $pendientes = [];
        foreach ($guia->apartados->sortBy('orden') as $apartado) {
            $entrega = $entregas->get($apartado->id);
            $firmantes = [];
            foreach ($apartado->firmas->sortBy('orden') as $slot) {
                $docente = $slot->docente;
                if (! $docente && $slot->asignatura_id) {
                    $docenteId = $proyecto->asignaturas->firstWhere('id', $slot->asignatura_id)?->pivot?->docente_id;
                    $docente = $docenteId ? User::find($docenteId) : null;
                }
                $firmantes[] = ['docente' => $docente, 'etiqueta' => $slot->etiqueta, 'requerida' => $slot->requerida];
            }
            if ($apartado->requiere_codigo) {
                $lider = $proyecto->equipo->grupoAcademico->liderProyecto;
                // El código, demo y aplicación solo pueden ser evaluados por la materia líder.
                $firmantes = array_values(array_filter($firmantes, fn ($f) => $lider && $f['docente']?->id === $lider->id));
                $firmantes = array_map(fn ($f) => [...$f, 'requerida' => true], $firmantes);
                if (! collect($firmantes)->contains(fn ($f) => $f['docente']?->id === $lider?->id)) {
                    $firmantes[] = ['docente' => $lider, 'etiqueta' => 'Docente de materia líder', 'requerida' => true];
                }
            }
            foreach ($apartado->requiere_codigo ? [] : $apartado->asignaturasContribuyentes->where('pivot.requiere_firma', true) as $materia) {
                if (! $apartado->firmas->contains('asignatura_id', $materia->id)) {
                    $docenteId = $proyecto->asignaturas->firstWhere('id', $materia->id)?->pivot?->docente_id;
                    if (! collect($firmantes)->contains(fn ($f) => $docenteId && $f['docente']?->id === $docenteId)) {
                        $firmantes[] = ['docente' => $docenteId ? User::find($docenteId) : null, 'etiqueta' => $materia->nombre, 'requerida' => true];
                    }
                }
            }
            if (! $entrega) {
                $pendientes[] = $apartado->titulo.': falta entrega.';
            }
            if (! $firmantes) {
                $pendientes[] = $apartado->titulo.': falta configurar un docente evaluador.';
            }
            foreach ($firmantes as &$firmante) {
                $revision = $entrega?->revisiones->firstWhere('revisor_id', $firmante['docente']?->id);
                $aprobada = $revision && $revision->resultado === 'aprobada' && $revision->firma_imagen && $revision->firmado_en
                    && hash_equals(app(FirmasDocentes::class)->contexto($proyecto, $apartado), $revision->firma_contexto ?? '');
                $firmante['revision'] = $aprobada ? $revision : null;
                $firmante['imagen'] = $aprobada && $incluirImagenes ? app(FirmasDocentes::class)->contextualizar($revision, $proyecto, $entrega) : null;
                if ($firmante['requerida'] && ! $aprobada) {
                    $pendientes[] = $apartado->titulo.': pendiente de aprobación firmada por '.($firmante['docente']?->nombre ?? $firmante['etiqueta']).'.';
                }
            }
            unset($firmante);
            $filas[$apartado->id] = ['entrega' => $entrega, 'firmantes' => $firmantes, 'fecha_limite' => app(PlazosProyecto::class)->fecha($proyecto, $apartado)];
        }
        if ($guia->apartados->isEmpty()) {
            $pendientes[] = 'La guía no tiene apartados.';
        }

        return compact('guia', 'proyecto', 'filas', 'pendientes');
    }

    public function pdf(array $datos, string $modo = 'BORRADOR'): string
    {
        $temporales = storage_path('app/private/pdf-tmp');
        $fuentes = storage_path('fonts');
        foreach ([$temporales, $fuentes] as $directorio) {
            if (! is_dir($directorio)) {
                mkdir($directorio, 0700, true);
            }
        }
        $pdf = Pdf::loadView('pdf.guia', [...$datos, 'modo' => $modo])
            ->setPaper('a4')->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false,
                'tempDir' => $temporales, 'fontDir' => $fuentes, 'fontCache' => $fuentes]);
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $dompdf->getCanvas()->page_text(500, 820, '{PAGE_NUM} / {PAGE_COUNT}', $dompdf->getFontMetrics()->getFont('DejaVu Sans'), 7, [0.3, 0.3, 0.3]);

        return $pdf->output();
    }

    public function renglones(?string $descripcion): array
    {
        $renglones = [];
        foreach (explode("\n", $descripcion ?? '') as $linea) {
            while (mb_strlen($linea) > 44) {
                $trozo = mb_substr($linea, 0, 44);
                $espacio = mb_strrpos($trozo, ' ');
                $corte = $espacio !== false && $espacio > 10 ? $espacio : 44;
                $renglones[] = mb_substr($linea, 0, $corte);
                $linea = ltrim(mb_substr($linea, $corte));
            }
            $renglones[] = $linea;
        }

        return array_chunk($renglones, 14);
    }

    public function huella(array $datos): string
    {
        $firmas = collect($datos['filas'])->map(fn ($fila) => [
            'entrega' => $fila['entrega']?->id,
            'fecha_limite' => $fila['fecha_limite']?->toIso8601String(),
            'firmas' => collect($fila['firmantes'])->map(fn ($f) => [$f['docente']?->id, $f['docente']?->nombre, $f['etiqueta'], $f['revision']?->firma_sha256, $f['revision']?->firmado_en?->toIso8601String()])->all(),
        ])->all();

        return hash('sha256', json_encode([$datos['guia']->toArray(), Arr::except($datos['proyecto']->toArray(), ['cierre']), $firmas], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
