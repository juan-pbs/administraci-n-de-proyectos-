<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\GrupoAcademico;
use App\Models\Proyecto;
use App\Servicios\CierresProyectos;
use App\Servicios\EstadoGuias;
use App\Soporte\SistemaInterfaz;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ControladorEstadoGuias extends Controller
{
    public function mostrar(Request $request, EstadoGuias $servicio)
    {
        $docente = $request->user()->loadMissing('role');
        abort_unless($docente->hasRole('docente_lider') && $docente->estado === 'activo', 403);
        $filtros = $request->validate(['periodo' => ['nullable', 'integer', 'min:1'], 'grupo' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', Rule::in(array_keys(EstadoGuias::ESTADOS))], 'buscar' => ['nullable', 'string', 'max:160'],
            'page' => ['nullable', 'integer', 'between:1,1000000'], 'por_pagina' => ['nullable', Rule::in([10, 20, 40])]]);
        $grupos = GrupoAcademico::with('periodo', 'carrera')->conMateriaLiderDelDocente($docente->id)->orderBy('nombre')->get();
        $periodos = $grupos->pluck('periodo')->unique('id')->sortByDesc('fecha_inicio')->values();
        if (! empty($filtros['periodo'])) {
            abort_unless($periodos->contains('id', (int) $filtros['periodo']), 403);
        }
        if (! empty($filtros['grupo'])) {
            abort_unless($grupos->contains('id', (int) $filtros['grupo']), 403);
        }
        $gruposVisibles = $grupos->when(! empty($filtros['periodo']), fn ($g) => $g->where('periodo_id', (int) $filtros['periodo']));
        if (! empty($filtros['grupo']) && ! $gruposVisibles->contains('id', (int) $filtros['grupo'])) {
            $filtros['grupo'] = null;
        }
        $proyectos = Proyecto::query()->whereHas('equipo.grupoAcademico', fn ($q) => $q->conMateriaLiderDelDocente($docente->id))
            ->whereHas('equipo', fn ($q) => $q->whereIn('grupo_academico_id', $gruposVisibles->pluck('id')))
            ->when(! empty($filtros['grupo']), fn ($q) => $q->whereHas('equipo', fn ($e) => $e->where('grupo_academico_id', $filtros['grupo'])))
            ->when(! empty($filtros['buscar']), function ($q) use ($filtros) {
                $termino = '%'.$filtros['buscar'].'%';
                $q->where(fn ($s) => $s->where('titulo', 'like', $termino)->orWhereHas('guiaIntegradora', fn ($g) => $g->where('nombre', 'like', $termino))->orWhereHas('equipo', fn ($e) => $e->where('nombre', 'like', $termino)));
            })
            ->with(['guiaIntegradora.periodo', 'guiaIntegradora.periodoFin', 'guiaIntegradora.asignatura.carrera',
                'guiaIntegradora.apartados.asignaturasContribuyentes', 'guiaIntegradora.apartados.firmas.docente', 'guiaIntegradora.apartados.firmas.asignatura',
                'equipo.integrantes', 'equipo.grupoAcademico.carrera', 'equipo.grupoAcademico.periodo', 'equipo.grupoAcademico.liderProyecto',
                'asignaturas', 'entregas.revisiones', 'cierre.prorrogas', 'documentosFinales:id,proyecto_id,created_at'])
            ->latest('id')->get();
        $filas = $proyectos->map(fn ($p) => $servicio->proyecto($p));
        $resumen = ['total' => $filas->count(), 'finalizadas' => $filas->where('estado', 'finalizada')->count(),
            'decision' => $filas->where('requiere_decision', true)->count(), 'prorrogas' => $filas->where('estado', 'prorroga_activa')->count(),
            'listas_pdf' => $filas->where('estado', 'lista_pdf')->count(), 'cerradas_pendientes' => $filas->where('estado', 'cerrada_pendientes')->count()];
        $filas = $filas->when(! empty($filtros['estado']), fn ($r) => $r->where('estado', $filtros['estado']));
        $porPagina = (int) ($filtros['por_pagina'] ?? 10);
        $pagina = min((int) ($filtros['page'] ?? 1), max(1, (int) ceil($filas->count() / $porPagina)));
        $filtros['page'] = $pagina;
        $filtros['por_pagina'] = $porPagina;
        $paginacion = new LengthAwarePaginator($filas->values()->forPage($pagina, $porPagina), $filas->count(), $porPagina, $pagina,
            ['path' => route('docente-lider.estado-guias'), 'query' => collect($filtros)->except('page')->all()]);

        return response()->view('modulos.docente-lider.estado-guias', ['guias' => $paginacion->getCollection()->groupBy('guia.id'), 'resumen' => $resumen, 'paginacion' => $paginacion,
            'filtros' => $filtros, 'estados' => EstadoGuias::ESTADOS, 'periodos' => $periodos, 'grupos' => $gruposVisibles,
            'navegacion' => SistemaInterfaz::navegacionPara('docente_lider'), 'roleName' => $docente->role->nombre_visible,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function prepararCierre(Request $request, Proyecto $proyecto, CierresProyectos $servicio)
    {
        $servicio->autorizar($request->user(), $proyecto);
        $cierre = $servicio->registrar($proyecto);
        if (! $cierre) {
            return redirect()->route('docente-lider.estado-guias')->with('status', 'El proyecto no tiene un cierre pendiente que resolver.');
        }

        return redirect()->route('docente-lider.cierres.mostrar', $proyecto);
    }
}
